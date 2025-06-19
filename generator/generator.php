<?php

if ($argc < 2) {
    echo "Usage: php generator.php <filename.json>\n";
    exit(10);
}


$inputFile = $argv[1];

date_default_timezone_set('Asia/Jakarta');

function addIndentLine(int $count, string $data, bool $newLine = true)
{
    return str_repeat(" ", 4 * $count) . $data . ($newLine ? "\n" : "");
}

function exportFields(array $fields): string
{
    $php = "[\n";

    foreach ($fields as $key => $definition) {
        $php .= addIndentLine(3, '"' . $key . '" => [');
        $indentCount = 4;
        foreach ($definition as $k => $v) {
            $str = (is_numeric($k) ? $k : '"' . $k . '"') . " => ";
            if (is_array($v)) {
                $str .= '[' . implode(', ', array_map(fn($val) => '"' . $val . '"', $v)) . ']';
            } elseif (is_bool($v)) {
                $str .= $v ? 'true' : 'false';
            } elseif (is_null($v)) {
                $str .= 'null';
            } elseif (is_numeric($v)) {
                $str .= $v;
            } else {
                $str .= '"' . addslashes($v) . '"';
            }
            $str .= ",";
            $php .= addIndentLine($indentCount, $str);
        }
        $php = rtrim($php, ",");
        $php .= addIndentLine(3, "],");
    }
    $php = rtrim($php, ",");
    $php .= addIndentLine(2, "]", false);
    return $php;
}
$BASE_DIR = realpath(__DIR__ . '/../');

$jsonFile = "$BASE_DIR/generator/{$inputFile}";
if (!file_exists($jsonFile)) {
    echo "JSON schema file not found: $jsonFile\n";
    exit(10);
}

$data = json_decode(file_get_contents($jsonFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error parsing JSON:" . json_last_error_msg() . "\n";
    exit(10);
}

$className = $data['class_name'] ?? exit("Missing class_name in JSON\n");
$tableName = $data['name'] ?? exit("Missing table name\n");
$timestamp = date('Y-m-d-His');
$migrationFileName = "{$className}Migration.php";
$migrationBasePath = "$BASE_DIR/app/Database/Migrations";
$migrationPath = "$migrationBasePath/{$timestamp}_$migrationFileName";
$dataClassPath = "$BASE_DIR/app/Models/DataModels/Data{$className}.php";
$migrationExist = false;
$dataClassExist = false;
if (!empty(glob("$migrationBasePath/*_$migrationFileName"))) {
    $migrationExist = true;
}
if (file_exists($dataClassPath)) {
    $dataClassExist = true;
}
if ($dataClassExist && $migrationExist) {
    echo "Target file exist\n";
    exit(2);
}


$useTimestamp = false;
$uuidFields = [];
$enumConstants = [];
$properties = [];
$fields = [];
$keys = [];
foreach ($data['fields'] as $field) {
    if (isset($field['preset']) && $field['preset'] === 'ced_timestamp') {
        $fields['created_at'] = [
            'type' => 'BIGINT',
            'unsigned' => true,
        ];
        $fields['updated_at'] = [
            'type' => 'BIGINT',
            'unsigned' => true,
            'null' => true
        ];
        $fields['deleted_at'] = [
            'type' => 'BIGINT',
            'unsigned' => true,
            'null' => true
        ];
        $useTimestamp = true;
        continue;
    }
    $name = $field['name'];
    $type = $field['type'] ?? 'VARCHAR';
    $nullable = $field['nullable'] ?? false;
    $phpType = "string";
    $phpDefault = '""';

    if (in_array($type, ['INT', 'BIGINT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'])) {
        $phpType = 'int';
        $default = '0';
    } elseif (in_array($type, ['FLOAT', 'DOUBLE', 'DECIMAL'])) {
        $phpType = 'float';
        $default = '0';
    }
    $fieldDef = ['type' => $type];

    if ($nullable) {
        $fieldDef["null"] = $null;
        $phpType = "?$phpType";
        $default = "null";
    }
    if ($type === 'BINARY' && ($field['length'] ?? 0) == 16) {
        $uuidFields[] = $name;
    }

    if (isset($field['length'])) {
        $fieldDef['constraint'] = $field['length'];
    }

    if ($field['type'] === 'ENUM' && isset($field['enums'])) {
        $fieldDef['constraint'] = $field['enums'];
        $_i = 0;
        foreach ($field['enums'] as $enumVal) {
            $const = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $enumVal));
            $constName = strtoupper($name) . "_" . $const;
            if ($_i == 0) {
                $phpDefault = "self::$constName";
            }
            $enumConstants[] = addIndentLine(1, "public const " . $constName . " = \"$enumVal\";");
            $_i++;
        }
        $enumConstants[] = "\n";
    }

    if (!empty($field['unsigned'])) {
        $fieldDef['unsigned'] = true;
    }

    if (!empty($field['unique'])) {
        $fieldDef['unique'] = true;
    }

    if (array_key_exists('default', $field)) {
        $fieldDef['default'] = $field['default'];
    }

    $fields[$name] = $fieldDef;
    $properties[] = addIndentLine(1, "public $phpType \$$name = $phpDefault;");
}
$hasPrimaryKey = isset($data['primaryKey']) && count($data['primaryKey']) > 0;
$primaryKeyLine = '';
if ($hasPrimaryKey) {
    $primaryKey = $data['primaryKey'][0]; // Use only the first key
    $primaryKeyLine = "->addPrimaryKey(\"{$primaryKey}\")";
}

$fieldsExport = exportFields($fields);

$indexLines = '';
$addedFields = []; // Prevent duplicate addKey for same field(s)
if (!empty($data['indexes']) && is_array($data['indexes'])) {
    foreach ($data['indexes'] as $index) {
        $fields = $index['fields'];
        $unique = !empty($index['unique']);

        // Skip if we've already added same key for these fields
        $keySignature = implode(',', $fields);
        if (in_array($keySignature, $addedFields)) {
            continue;
        }
        $addedFields[] = $keySignature;

        $fieldExport = count($fields) === 1
            ? var_export($fields[0], true)
            : "[\"" . implode("\", \"", $fields) . "\"]";

        $indexLines .= "\n            ->addKey({$fieldExport}, false";
        if ($unique) {
            $indexLines .= ", true";
        }
        $indexLines .= ")";
    }
}
$migrationContent = <<<PHP
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class {$className}Migration extends Migration
{
    private \$table = "{$tableName}";
    public function up()
    {
        \$fields = {$fieldsExport};
        \$this->forge->addField(\$fields)
            {$primaryKeyLine}{$indexLines}
            ->createTable(\$this->table, true);
    }

    public function down()
    {
        \$this->forge->dropTable(\$this->table, true);
    }
}
PHP;

// 7. Write file
if (!is_dir(dirname($migrationPath))) {
    mkdir(dirname($migrationPath), 0755, true);
}
if (!$migrationExist) {
    file_put_contents($migrationPath, $migrationContent);
    echo "Migration created: $migrationPath\n";
} else {
    echo "Migration already exist\n";
}

if ($useTimestamp) {
    $properties[] = "\n";
    $properties[] = addIndentLine(1, "public ?int \$created_at = null;");
    $properties[] = addIndentLine(1, "public ?int \$edited_at = null;");
    $properties[] = addIndentLine(1, "public ?int \$deleted_at = null;");
}
$baseClass = $uuidFields ? 'DataUuid' : 'DataModel';
$traitUse = $useTimestamp ? addIndentLine(1, "use DataTimeTrait;") : "";
$output = "<?php\n\nnamespace App\\Models\\DataModels;\n\nclass Data$className extends $baseClass\n{\n$traitUse";
if ($enumConstants) {
    $output .= "\n" . implode("", $enumConstants);
}

$output .= implode("", $properties);
// UUID method
if ($uuidFields) {
    $array = implode(', ', array_map(fn($v) => "\"$v\"", $uuidFields));
    $output .= "\n" . addIndentLine(1, "public function getUuidFields(): array")
        . addIndentLine(1, "{")
        . addIndentLine(2, "return [$array];")
        . addIndentLine(1, "}");
}

if ($useTimestamp || $uuidFields) {
    $output .= "\n" .
        addIndentLine(1, "public static function fromArray(array \$data): static")
        . addIndentLine(1, "{");
    if ($useTimestamp) {
        $output .= addIndentLine(2, "self::ensureTime([\"created_at\"], \$data);");
    }
    $output .= addIndentLine(2, "\$instance = parent::fromArray(\$data);");
    if ($uuidFields) {
        $output .= addIndentLine(2, "\$instance->ensureUuid();");
    }
    $output .= addIndentLine(2, "return \$instance;") . addIndentLine(1, "}");
}

$output .= "}\n";

if (!$dataClassExist) {
    file_put_contents($dataClassPath, $output);
    echo "DataModel created: $dataClassPath\n";
} else {
    echo "DataModel already exist\n";
}

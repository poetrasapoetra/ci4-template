<?php

namespace App\Models\DataModels;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;

class DataValidator
{
    protected Validator $validator;

    function __construct()
    {
        $validator = new Validator();
        $validator->resolver()->registerPrefix(base_url("schemas/"), APPPATH . "/Models/DataModels/Schemas");
        $validator->setMaxErrors(20);
        $this->validator = $validator;
    }

    function validate(array|object $data, string $schema_path)
    {

        $validator = $this->validator;
        $schema_path = base_url("schemas/$schema_path");
        $result = $validator->validate(Helper::toJSON($data), $schema_path);
        if ($result->isValid()) {
            return true;
        } else {
            $formatter = new ErrorFormatter();
            $customFormatter = function (ValidationError $error) use ($formatter) {
                // $schema = $error->schema()->info();
                $errorKeyword = $error->keyword();
                $errorArgs = $error->args();
                $errorArgsFlat = [...$errorArgs];
                foreach ($errorArgs as $key => $val) {
                    if (is_array($val)) {
                        $errorArgsFlat[$key] = implode(', ', $val);
                    }
                }

                // Try to translate error message.
                // If translation not found then use default error message from library.
                $langMessagePath = "validation.$errorKeyword";
                $translatedMessage = lang($langMessagePath, $errorArgsFlat);
                if ($translatedMessage == $langMessagePath) {
                    $translatedMessage = $formatter->formatErrorMessage($error);
                }
                if (!isDev()) {
                    // return minimal message if in non-development env.
                    return [
                        'path' => $error->data()->fullPath(),
                        'message' => $translatedMessage
                    ];
                }
                return [
                    'path' => $error->data()->fullPath(),
                    'message' => lang("validation.$errorKeyword", $errorArgsFlat),
                    'error' => [
                        'keyword' => $errorKeyword,
                        'args' => $errorArgs,
                        'message' => $translatedMessage
                    ]
                ];
            };

            $customKey = function (ValidationError $error): string {
                return implode('.', $error->data()->fullPath());
            };
            return $formatter->format($result->error(), true, $customFormatter, $customKey);
        }
    }
}

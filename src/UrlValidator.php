<?php

namespace Analyzer;

use Valitron\Validator as ValitronValidator;

class UrlValidator
{
    public function validate(array $urlData): array
    {
        $errors = [];

        if (empty($urlData['name'])) {
            $errors['name'] = "URL не должен быть пустым";
            return $errors;
        }

        $validator = new ValitronValidator($urlData);
        $validator->rule('url', 'name');
        $validator->rule('lengthMax', 'name', 255);

        if (!$validator->validate()) {
            $errors['name'] = "Некорректный URL";
        }

        return $errors;
    }
}

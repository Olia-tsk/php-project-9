<?php

namespace Analyzer;

class Validator
{
    public function validate(array $urlData): array
    {
        $errors = [];
        $pattern = '^(https?:\/\/)?([\w\.]+)\.([a-z]{2,6}\.?)(\/[\w\.]*)*\/?$';
        if (empty($urlData['name'])) {
            $errors['name'] = "URL не должен быть пустым";
        } elseif (!mb_ereg_match($pattern, $urlData['name'])) {
            $errors['name'] = "Некорректный URL";
        } elseif (strlen($urlData['name']) > 255) {
            $errors['name'] = "Слишком длинный URL";
        }

        return $errors;
    }
}

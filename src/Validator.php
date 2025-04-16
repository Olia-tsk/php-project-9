<?php

namespace Analyzer;

class Validator
{
    public function validate(array $urlData): array
    {
        $errors = [];
        $pattern = 'https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.~#?&\/\/=]*)';
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

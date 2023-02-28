<?php

namespace App\Service;

class CleanArray
{
    public function cleanContent($array): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            $new_key = str_replace("\x00App\Entity\Content\x00", "", $key);
            $new_key = str_replace("\x00", "", $new_key);
            $output[$new_key] = $value;
        }

        return $output;
    }

    public function cleanAccount($array): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            $new_key = str_replace("\x00App\Entity\Account\x00", "", $key);
            $new_key = str_replace("\x00", "", $new_key);
            $output[$new_key] = $value;
        }

        return $output;
    }

    public function cleanFile($array): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            $new_key = str_replace("\x00App\Entity\File\x00", "", $key);
            $new_key = str_replace("\x00", "", $new_key);
            $output[$new_key] = $value;
        }

        return $output;
    }

    public function cleanComment($array): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            $new_key = str_replace("\x00App\Entity\Comment\x00", "", $key);
            $new_key = str_replace("\x00", "", $new_key);
            $output[$new_key] = $value;
        }

        return $output;
    }


    public function cleanAccountMatrix(array $array): array
    {
        $singleOutput = [];
        $matrix = [];

        foreach ($array as $single) {
            foreach ($single as $key => $value) {
                $new_key = str_replace('App\Entity\Account', '', $key);
                $new_key = substr($new_key, 2);
                $singleOutput[$new_key] = $value;
            }
            $matrix[] = $singleOutput;
        }

        return $matrix;
    }

    public function cleanContentMatrix(array $array): array
    {
        $singleOutput = [];
        $matrix = [];

        foreach ($array as $single) {
            foreach ($single as $key => $value) {
                $new_key = str_replace('App\Entity\Content', '', $key);
                $new_key = substr($new_key, 2);
                $singleOutput[$new_key] = $value;
            }
            $matrix[] = $singleOutput;
        }

        return $matrix;
    }

    public function cleanContentLikeMatrix(array $array): array
    {
        $singleOutput = [];
        $matrix = [];

        foreach ($array as $single) {
            if (is_array($single)) {

                foreach ($single as $key => $value) {
                    $new_key = str_replace('App\Entity\ContentLike', '', $key);
                    $new_key = substr($new_key, 2);
                    $singleOutput[$new_key] = $value;
                }
                $matrix[] = $singleOutput;
            } else {
                end($array);
                $lastKey = key($array);
                $matrix[] = [$lastKey => $single];
            }
        }

        return $matrix;
    }

    public function cleanCommentMatrix(array $array): array
    {
        $singleOutput = [];
        $matrix = [];

        foreach ($array as $single) {
            foreach ($single as $key => $value) {
                $new_key = str_replace('App\Entity\Comment', '', $key);
                $new_key = substr($new_key, 2);
                $singleOutput[$new_key] = $value;
            }
            $matrix[] = $singleOutput;
        }

        return $matrix;
    }

    public function cleanCommentLikeMatrix(array $array): array
    {
        $singleOutput = [];
        $matrix = [];

        foreach ($array as $single) {
            if (is_array($single)) {

                foreach ($single as $key => $value) {
                    $new_key = str_replace('App\Entity\CommentLike', '', $key);
                    $new_key = substr($new_key, 2);
                    $singleOutput[$new_key] = $value;
                }
                $matrix[] = $singleOutput;
            } else {
                end($array);
                $lastKey = key($array);
                $matrix[] = [$lastKey => $single];
            }
        }

        return $matrix;
    }
}
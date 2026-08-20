<?php
declare(strict_types=1);

const REVIEWS_FILE = __DIR__ . '/../storage/reviews.json';

function load_reviews(): array
{
    if (!is_file(REVIEWS_FILE)) {
        return [];
    }

    $contents = file_get_contents(REVIEWS_FILE);
    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $reviews = json_decode($contents, true);
    if (!is_array($reviews)) {
        return [];
    }

    return array_values(array_filter($reviews, static function ($review): bool {
        return is_array($review)
            && isset($review['id'], $review['name'], $review['review'])
            && is_string($review['id'])
            && is_string($review['name'])
            && is_string($review['review']);
    }));
}

function save_reviews(array $reviews): bool
{
    $directory = dirname(REVIEWS_FILE);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return false;
    }

    $handle = fopen(REVIEWS_FILE, 'c+');
    if ($handle === false) {
        return false;
    }

    $saved = false;
    if (flock($handle, LOCK_EX)) {
        $json = json_encode(array_values($reviews), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            rewind($handle);
            if (ftruncate($handle, 0) && fwrite($handle, $json . PHP_EOL) !== false) {
                fflush($handle);
                $saved = true;
            }
        }
        flock($handle, LOCK_UN);
    }

    fclose($handle);
    return $saved;
}

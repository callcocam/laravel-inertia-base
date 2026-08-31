<?php

namespace App\Support\Translation;

use Illuminate\Translation\FileLoader;

class MergingFileLoader extends FileLoader
{
    /**
     * Load the messages for the given locale, merging the group's
     * homonymous directory (e.g. lang/pt_BR/app/) over the flat file.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array<string, mixed>
     */
    public function load($locale, $group, $namespace = null)
    {
        $lines = parent::load($locale, $group, $namespace);

        if (($namespace !== null && $namespace !== '*') || $group === '*') {
            return $lines;
        }

        foreach ($this->paths as $path) {
            $directory = "{$path}/{$locale}/{$group}";

            if ($this->files->isDirectory($directory)) {
                $lines = array_replace_recursive($lines, $this->loadDirectory($directory));
            }
        }

        return $lines;
    }

    /**
     * Recursively load a directory of translation files: each `.php` file
     * becomes a key and each subdirectory a nested level.
     *
     * @return array<string, mixed>
     */
    protected function loadDirectory(string $directory): array
    {
        $lines = [];

        foreach ($this->files->directories($directory) as $subdirectory) {
            $lines[basename($subdirectory)] = $this->loadDirectory($subdirectory);
        }

        foreach ($this->files->files($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $this->files->getRequire($file->getPathname());

            if (is_array($contents)) {
                $key = $file->getFilenameWithoutExtension();
                $lines[$key] = array_replace_recursive($lines[$key] ?? [], $contents);
            }
        }

        return $lines;
    }
}

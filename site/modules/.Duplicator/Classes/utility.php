<?php


class DUP_Util
{
    static private $limitItems = 0;

    static public function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    public static function foldersize($path)
    {
        $total_size = 0;
        if (!is_dir($path)) return 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/') . '/';

        foreach ($files as $t) {
            if ($t <> "." && $t <> "..") {
                $currentFile = $cleanPath . $t;
                if (is_dir($currentFile)) {
                    $size = self::foldersize($currentFile);
                    $total_size += $size;
                } else {
                    $size = filesize($currentFile);
                    $total_size += $size;
                }
            }
        }

        return $total_size;
    }

    public static function filesize($file)
    {
        $filesize = filesize($file); // bytes
        return round($filesize / 1024 / 1024, 1); // in MB
    }

    public static function human_filesize($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    // 2017-01-28_13-55-17-anything-i-find-descriptive.package.zip
    public static function formatFilename($name, $extension)
    {
        $filename = $name;
        $_filename = $filename;
        $date = date('Y-m-d_H-i-s'); // +is
        $filename = $date . '-' . str_replace(' ', '_', $name);
        $filename .= $extension ? '.' . $extension : '';

        /*
        $n = 0;

        while(file_exists($this->localPath . DIRECTORY_SEPARATOR . $filename))
        {
            $filename = $date . "-" . $_filename . '-' . (++$n) . '.' . $extension;
        }*/

        return $filename;
    }
/*
    public static function zipData($source, $destination, $excluded = array())
    {
        if (extension_loaded('zip')) {
            if (file_exists($source)) {
                $zip = new \ZipArchive();

                if ($zip->open($destination, \ZIPARCHIVE::CREATE)) {
                    $source = realpath($source);
                    if (is_dir($source)) {
                        $filter = function ($file, $key, $iterator) use ($excluded) {
                            $includeEmptyDir = true;
                            if ($includeEmptyDir && $iterator->isDir() && !in_array($file->getPathname(), $excluded['exclude'])) {
                                return true;
                            }
                            if ($iterator->hasChildren() && !in_array($file->getPathname(), $excluded['exclude'])) {
                                return true;
                            } elseif ($file->isFile() && in_array($file->getPathname(), $excluded['exclude'])) {
                                return false;
                            } elseif ($file->isFile() && in_array($file->getExtension(), $excluded['extension'])) {
                                return false;
                            }
                            return $file->isFile();
                        };

                        $innerIterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveCallbackFilterIterator($innerIterator, $filter)
                        );

                        foreach ($iterator as $pathname => $fileInfo) {
                            $file = realpath($pathname);
                            if (is_dir($file)) {
                                $zip->addEmptyDir(str_replace($source . '', '', $file . ''));
                            } elseif (is_file($file)) {
                                $zip->addFromString(str_replace($source . '', '', $file), file_get_contents($file));
                            }
                        }
                    } else if (is_file($source)) {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }
                return $zip->close();
            }
        }
        return false;
    }*/

    public static function keep($path, $size = 1, $deadline = null)
    {
        if (empty($path)) return false;
        $files = scandir($path);
        $last = null;
        if (!count($files)) return false;
        foreach ($files as $file) {
            if (strrchr($file, '.') != strrchr(DUP_PACKAGE_EXTENSION, '.')) continue;
            $date = filemtime($path . DIRECTORY_SEPARATOR . $file);
            if ($deadline && $date < $deadline) continue;
            $last[$file] = $date;
        }
        if (is_array($last)) {
            arsort($last);
            $last = array_keys($last);
            if (!count($last)) return false;
            return array_slice($last, 0, $size);
        }

        return false;
    }

    public static function clean($path, $size = 1, $deadline = null)
    {
        if (!DUP_Util::keep($path)) return array();

        $cleaned = array();

        $error_message = __("Removing %1s from %2s failed!");
        $keep = DUP_Util::keep($path, $size, $deadline);
        foreach (new \DirectoryIterator($path) as $backup) {
            if ($backup->getExtension() != 'zip') continue;
            $backup = $backup->getFilename();
            if (in_array($backup, $keep)) continue;
            if (DUP_Util::deleteFile($path . DIRECTORY_SEPARATOR . $backup)) {
                $cleaned[] = $backup;
                continue;
            } else DUP_Logs::log(sprintf($error_message, $backup, $path));
        }

        return $cleaned;
    }

    public static function deleteFile($path)
    {
        if (file_exists($path) && !is_dir($path)) {
            unlink($path);
            return true;
        }

        return false;
    }


    public static function zipData($source, $destination, $excluded = array())
    {
        if (extension_loaded('zip')) {
            if (file_exists($source)) {
                $zip = new \ZipArchive();

                if ($zip->open($destination, \ZIPARCHIVE::CREATE)) {
                    $source = realpath($source);
                    if (is_dir($source)) {
                        $filter = function ($file, $key, $iterator) use ($excluded) {
                            $includeEmptyDir = true;
                            if ($includeEmptyDir && $iterator->isDir() && !in_array($file->getPathname(), $excluded['exclude'])) {
                                return true;
                            }
                            if ($iterator->hasChildren() && !in_array($file->getPathname(), $excluded['exclude'])) {
                                return true;
                            } elseif ($file->isFile() && in_array($file->getPathname(), $excluded['exclude'])) {
                                return false;
                            } elseif ($file->isFile() && in_array($file->getExtension(), $excluded['extension'])) {
                                return false;
                            }
                            return $file->isFile();
                        };

                        $innerIterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveCallbackFilterIterator($innerIterator, $filter)
                        );

                        foreach ($iterator as $pathname => $fileInfo) {
                            $file = realpath($pathname);
                            if (is_dir($file)) {
                                $zip->addEmptyDir(str_replace($source . '', '', $file . ''));
                            } elseif (is_file($file)) {
                                //$zip->addFromString(str_replace($source . '', '', $file), file_get_contents($file));
                                //DUP_Logs::log('adding '.str_replace($source . '', '', $file));
                                $zip->addFile($file, str_replace($source . '', '', $file));
                                self::$limitItems++;

                                if(self::$limitItems > DUP_ZIP_FLUSH_TRIGGER)
                                {
                                    $zip->close();
                                    $zip->open($destination);
                                    DUP_Util::FcgiFlush();
                                    //DUP_Logs::log("Items archived [". self::$limitItems ."] flushing response.");
                                    self::$limitItems = 0;
                                }
                            }
                        }
                    } else if (is_file($source)) {
                        //$zip->addFromString(basename($source), file_get_contents($source));
                        self::$limitItems++;

                        if(self::$limitItems > DUP_ZIP_FLUSH_TRIGGER)
                        {
                            $zip->close();
                            $zip->open($destination);
                            DUP_Util::FcgiFlush();
                            //DUP_Logs::log("Items archived [". self::$limitItems ."] flushing response.");
                            self::$limitItems = 0;
                        }
                        //DUP_Logs::log('adding(2) '. $source);
                        $zip->addFile($source, basename($source));
                    }
                }


                return $zip->close();
            }
        }
        return false;
    }

    public static function getTotalPackages($path, $extension)
    {
        //$ext = '.' . $extension;
        if (!empty($path)) {
            $files = scandir($path);
            if (!count($files)) return 0;
            $n = 0;
            foreach ($files as $file) {
                if (strrchr($file, $extension) == false) continue;
                $n++;

            }
            return $n;
        }

        return 0;
    }

    public static function getPackages($path, $extension)
    {
        //$ext = '.' . $extension;
        if (!empty($path)) {
            $files = scandir($path);
            if (!count($files)) return 0;
            $n = 0;
            foreach ($files as $file) {
                if (strrchr($file, $extension) == false) continue;

                $n++;

            }
            return $files;
        }

        return 0;
    }

    public static function getPackagesDetails($path, $extension)
    {
        //$extension = '.' . $extension;
        $data = null;
        $rows = array();
        if (!empty($path)) {
            $files = scandir($path);
            if (!count($files)) return $data;
            $n = 0;
            foreach ($files as $file) {
                if (strrchr($file, $extension) == false) continue;
                $parts = explode('-', $file);
                $name = array_reverse($parts)[0];
                array_pop($parts);
                $tsstr = implode('-', $parts);
                $ts = date_create_from_format(DUP_TIMESTAMP_FORMAT, $tsstr);
                $createdOn = ($ts === false) ? 'invalid timestamp' : wireRelativeTimeStr($ts->getTimestamp());

                $data = array(
                    $name,
                    $createdOn,
                    self::human_filesize(filesize($path . DIRECTORY_SEPARATOR . $file))
                );
                array_push($rows, $data);

                $n++;
            }

            return array_reverse($rows);
        }

    }

    public static function isWinOS()
    {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }

    static public function safePath($path)
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * Get current microtime as a float. Can be used for simple profiling.
     */
    static public function getMicrotime()
    {
        return microtime(true);
    }

    static public function setMemoryLimit($value = 30)
    {
        $prevLimit = ini_get('memory_limit');
        if(!$prevLimit) return false;
        $timeLimit = (int) ($prevLimit > $value ? $prevLimit : $value);
        return @ini_set('memory_limit', $timeLimit);
    }

    static public function setMaxExecutionTime($value = 30)
    {
        $prevLimit = ini_get('max_execution_time');
        if(!$prevLimit) return false;
        $timeLimit = (int) ($prevLimit > $value ? $prevLimit : $value);
        return @ini_set('max_execution_time', $timeLimit);
    }

    static public function isEnabled($func) {
        return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
    }

    static public function FcgiFlush() {
        echo(str_repeat(' ', 300));
        @flush();
    }
}
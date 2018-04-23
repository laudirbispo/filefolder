<?php declare(strict_types=1);
namespace laudirbispo\FileAndFolder;

/**
 * Copyright (c) Laudir Bispo  (laudirbispo@outlook.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     (c) Laudir Bispo  (laudirbispo@outlook.com)
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use DirectoryIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Folder
{
	use TraitFileAndFolder;
	
	/**
     * Path to Folder.
     *
     * @var string
     */
	protected $path = '';
	
	/**
     * The chmmod for Linux platform
     *
     * @var int (octal)
     */
    const DEFAULT_MODE = 0755;
	
	public static $errors = array();
	
	public static $messages = array();
	
	public function __construct($path = null, $create = false, $mode = false)
    {
		//...
    }
	
	/**
	 * Check if the current path exists
	 */
	public static function exists (string $path) : bool
	{
		return (file_exists($path));
	}
	
	/**
	 * Returns true if the file exists and can be modified
	 */
	public static function isWritable (string $path) : bool
	{
		if (!self::exists($path))
			return false;
		
		return (is_writable($path));
	}
	
	/**
	 * Create directorys
	 */
	public static function create (string $path, int $mode = self::DEFAULT_MODE, bool $recursive = true) : bool
	{
		$path = self::normalize($path);
		
		if (self::exists($path))
			return true;

		$oldumask = umask(0);
		$dir = mkdir($path, $mode, $recursive); 
		umask($oldumask); 
		return $dir;
	}
	
	/**
     * Returns an array of nested directories and files in each directory
     *
     * @param string|null $path the directory path to build the tree from
     * @param array|bool $exceptions Either an array of files/folder to exclude
     *   or boolean true to not grab dot files/folders
     * @param string|null $type either 'file' or 'dir'. Null returns both files and directories
     * @return array Array of nested directories and files in each directory
     */
    public static function tree (string $path, $exceptions = false, $type = null)
    {
        if (!self::exists($path))
			return false;
		
        $files = [];
        $directories = [$path];

        if (is_array($exceptions)) {
            $exceptions = array_flip($exceptions);
        }
        $skipHidden = false;
        if ($exceptions === true) {
            $skipHidden = true;
        } elseif (isset($exceptions['.'])) {
            $skipHidden = true;
            unset($exceptions['.']);
        }

        try {
            $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME | RecursiveDirectoryIterator::CURRENT_AS_SELF);
            $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        } catch (Exception $e) {
            if ($type === null) {
                return [[], []];
            }

            return [];
        }

        foreach ($iterator as $itemPath => $fsIterator) {
            if ($skipHidden) {
                $subPathName = $fsIterator->getSubPathname();
                if ($subPathName{0} === '.' || strpos($subPathName, DIRECTORY_SEPARATOR . '.') !== false) {
                    continue;
                }
            }
            $item = $fsIterator->current();
            if (!empty($exceptions) && isset($exceptions[$item->getFilename()])) {
                continue;
            }

            if ($item->isFile()) {
                $files[] = $itemPath;
            } elseif ($item->isDir() && !$item->isDot()) {
                $directories[] = $itemPath;
            }
        }
        if ($type === null) {
            return [$directories, $files];
        }
        if ($type === 'dir') {
            return $directories;
        }

        return $files;
    }
	
	/**
     * Change the mode on a directory structure recursively. This includes changing the mode on files as well.
     *
     * @param string $path The path to chmod.
     * @param int|bool $mode Octal value, e.g. 0755.
     * @param bool $recursive Chmod recursively, set to false to only change the current directory.
     * @param array $exceptions Array of files, directories to skip.
     * @return bool Success.
     */
    public static function setChmod (string $path, 
									 int $mode = self::DEFAULT_MODE, 
									 bool $recursive = true, 
									 array $exceptions = []
									)
    {
        if (!self::exists($path))
		{
			self::$errors[] = "[{$path}] - Não existe.";
			return false;
		}
		
		if (!self::isValidChmod($mode))
		{
			self::$messages[] = sprintf('O valor %s, é inválido para chmod. O valor padrão %s, foi aplicado para o diretório %s.', $mode, self::DEFAULT_MODE, $path);
		}

        if ($recursive === false && is_dir($path)) 
		{
            //@codingStandardsIgnoreStart
            if (@chmod($path, intval($mode, 8))) 
			{
                //@codingStandardsIgnoreEnd
                self::$messages[] = sprintf('%s alterado para %s', $path, $mode);

                return true;
            }
            self::$errors[] = sprintf('%s não alterado para %s', $path, $mode);
            return false;
        }

        if (is_dir($path)) 
		{
            $paths = self::tree($path);

            foreach ($paths as $type) 
			{
                foreach ($type as $fullpath) 
				{
                    $check = explode(DIRECTORY_SEPARATOR, $fullpath);
                    $count = count($check);

                    if (in_array($check[$count - 1], $exceptions)) 
                        continue;

                    //@codingStandardsIgnoreStart
                    if (@chmod($fullpath, intval($mode, 8))) 
                        //@codingStandardsIgnoreEnd
                        self::$messages[] = sprintf('%s alterado para %s', $fullpath, $mode);
                    else 
                        self::$errors[] = sprintf('%s não alterado para %s', $fullpath, $mode);
                }
            }

            if (empty(self::$errors)) 
                return true;   
        }

        return false;
    }
	
	/**
     * Recursively Remove directories if the system allows.
     *
     * @param string|null $path Path of directory to delete
     * @return bool Success
     */
    public static function delete (string $path = null)
    {
        if (!$path) 
            return false;

        if (is_dir($path)) {
            try {
                $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::CURRENT_AS_SELF);
                $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
            } catch (\Exception $e) {
                return false;
            }

            foreach ($iterator as $item) {
                $filePath = $item->getPathname();
                if ($item->isFile() || $item->isLink()) {
                    //@codingStandardsIgnoreStart
                    if (@unlink($filePath)) {
                        //@codingStandardsIgnoreEnd
                        self::$messages[] = sprintf('%s removido.', $filePath);
                    } else {
                        self::$errors[] = sprintf('%s não removido.', $filePath);
                    }
                } elseif ($item->isDir() && !$item->isDot()) {
                    //@codingStandardsIgnoreStart
                    if (@rmdir($filePath)) {
                        //@codingStandardsIgnoreEnd
                        self::$messages[] = sprintf('%s removido.', $filePath);
                    } else {
                        self::$errors[] = sprintf('%s não removido', $filePath);
                        return false;
                    }
                }
            }

            $path = rtrim($path, DIRECTORY_SEPARATOR);
            //@codingStandardsIgnoreStart
            if (@rmdir($path)) {
                //@codingStandardsIgnoreEnd
                self::$messages[] = sprintf('%s removido', $path);
            } else {
                self::$errors[] = sprintf('%s não removido', $path);
                return false;
            }
        }

        return true;
    }
	
	
	/**
     * Returns $path with $element added, with correct slash in-between.
     *
     * @param string $path Path
     * @param string|array $element Element to add at end of path
     * @return string Combined path
     */
    public static function addPathElement($path, $element)
    {
        $element = (array)$element;
        array_unshift($element, rtrim($path, DIRECTORY_SEPARATOR));
        return implode(DIRECTORY_SEPARATOR, $element);
    }
	
	/**
     * Returns true if given $path is a Windows path.
     *
     * @param string $path Path to check
     * @return bool true if windows path, false otherwise
     */
    public static function isWindowsPath(string $path = '') : bool
    {
        return (preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\');
    }
 
	
	public static function isValidChmod (int $mode) : bool
	{
		if (filter_var($mode, FILTER_VALIDATE_INT, array('flags' => FILTER_FLAG_ALLOW_OCTAL)))
			return true;
		else
			return false;
	}
	
}

<?php

namespace Christophrumpel\NovaNotifications;

use App\Logging\QMLog;
use App\Utils\Files\FileHelper;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ClassFinder
{
    /**
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->getAppNamespace();
    }

    /**
     * @param string $nameSpace
     * @return Collection
     */
    public function find(string $nameSpace): Collection
    {
        $composer = require base_path('vendor/autoload.php');

        return collect($composer->getClassMap())->filter(function ($value, $key) use ($nameSpace) {
            return Str::startsWith($key, $nameSpace);
        });
    }

    /**
     * Find classes which are extending a specific class.
     *
     * @param string $classNameToFind
     * @param array $namespacesToSearch
     * @return Collection
     */
    public function findByExtending(string $classNameToFind, array $namespacesToSearch): Collection
    {

        $children = [];
        foreach($namespacesToSearch as $namespace){
            $all = FileHelper::getClassesInNamespace($namespace, true);
            foreach( $all as $class ){
                if( is_subclass_of( $class, $classNameToFind ) ){
                    $children[] = $class;
                }
            }
        }
        return collect($children);

        $composer = require base_path('vendor/autoload.php');

        $filtered = collect($composer->getClassMap())
            ->keys()
            ->filter(function ($className) {
                return $className !== 'Illuminate\Filesystem\Cache';
            });

        $all = $filtered->all();
        sort($all);

        $filtered = $filtered->filter(function ($className) use ($namespacesToSearch) {
            return collect($namespacesToSearch)
                ->filter(function ($namespace) use ($className) {
                    return Str::startsWith($className, $namespace);
                })
                ->count();
        });

        $subClasses = $filtered
            ->filter(function ($className) use ($classNameToFind) {
                try {
                    $classInfo = new ReflectionClass($className);
                } catch (\Exception $e) {
                    return false;
                }

                return $classInfo->isSubclassOf($classNameToFind);
            });

        return $subClasses;
    }

    /**
     * @param string $folder
     * @param bool $recursive
     * @return string[]
     */
    public static function getClassesInFolder(string $folder, bool $recursive = true):array{
        $folder = self::getAbsolutePathFromRelative($folder);
        $finder = new Finder();
        try {
            $finder->in($folder)->files();
        } catch (DirectoryNotFoundException $e){
            QMLog::info($e->getMessage());
            return [];
        }
        if(!$recursive){$finder->depth('== 0');}
        $classes = [];
        foreach ($finder as $file) {
            $classes[] = self::pathToClass($file->getRealPath());
        }
        return $classes;
    }
}

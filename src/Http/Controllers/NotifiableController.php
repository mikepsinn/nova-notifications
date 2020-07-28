<?php

namespace Christophrumpel\NovaNotifications\Http\Controllers;

use Christophrumpel\NovaNotifications\ClassFinder;
use ReflectionClass;

class NotifiableController extends ApiController
{
    /**
     * @var ClassFinder
     */
    private $classFinder;

    /**
     * NotifiableController constructor.
     *
     * @param ClassFinder $classFinder
     */
    public function __construct(ClassFinder $classFinder)
    {
        $this->classFinder = $classFinder;
    }

    public function index()
    {
        $notifiableNamespaces = config('nova-notifications.notifiableNamespaces');
        $inNameSpace = $this->classFinder->findByExtending('Illuminate\Database\Eloquent\Model',
            $notifiableNamespaces);
        $notifiableClasses = $inNameSpace
            ->filter(function ($className) {
                $classInfo = new ReflectionClass($className);

                return in_array('Illuminate\Notifications\Notifiable', $classInfo->getTraitNames());
            });
        $arr = $notifiableClasses->map(function ($className) {
            return [
                'name' => str_replace('\\', '.', $className),
                'options' => $className::all(),
            ];
        });

        $options = $arr->map(function ($notifiable) {
            return [
                'name' => $notifiable['name'],
            ];
        })->toArray();

        return [
            'data' => $modelClasses->values(),
            'filter' => [
                'name' => __('Notifiables'),
                'options' => $options,
            ],
        ];
    }
}

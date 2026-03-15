<?php

namespace Frex\CrudGenerator\Commands;

use Frex\CrudGenerator\ModelGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class GeneratorCommand.
 */
abstract class GeneratorCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Do not make these columns fillable in Model or views.
     *
     * @var array
     */
    protected $unwantedColumns = [
        'id',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Table name from argument.
     *
     * @var string
     */
    protected $table = null;

    /**
     * Formatted Class name from Table.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Store the DB table columns.
     *
     * @var array
     */
    private $tableColumns = null;

    /**
     * Model Namespace.
     *
     * @var string
     */
    protected $modelNamespace = 'App';

    /**
     * dataTable Namespace.
     *
     * @var string
     */
    protected $dataTableNamespace = 'App\DataTables';
    

    /**
     * Controller Namespace.
     *
     * @var string
     */
    protected $controllerNamespace = 'App\Http\Controllers';

    /**
     * Application Layout
     *
     * @var string
     */
    protected $layout = 'layouts.app';

    /**
     * Custom Options name
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new controller creator command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->unwantedColumns = $this->packageConfig('model.unwantedColumns', $this->unwantedColumns);
        $this->modelNamespace = $this->packageConfig('model.namespace', $this->modelNamespace);
        $this->dataTableNamespace = $this->packageConfig('dataTable.namespace', $this->dataTableNamespace);
        $this->controllerNamespace = $this->packageConfig('controller.namespace', $this->controllerNamespace);
        $this->layout = $this->packageConfig('layout', $this->layout);
    }

    /**
     * Generate the controller.
     *
     * @return $this
     */
    abstract protected function buildController();


    /**
     * Generate the DataTable.
     *
     * @return $this
     */
    abstract protected function buildDataTable();
    

    /**
     * Generate the Model.
     *
     * @return $this
     */
    abstract protected function buildModel();

    /**
     * Generate the views.
     *
     * @return $this
     */
    abstract protected function buildViews();

    /**
     * Build the directory if necessary.
     *
     * @param string $path
     *
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Write the file/Class.
     *
     * @param $path
     * @param $content
     */
    protected function write($path, $content)
    {
        $this->files->put($path, $content);
    }

    /**
     * Get the stub file.
     *
     * @param string $type
     * @param boolean $content
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function getStub($type, $content = true)
    {
        $stub_path = $this->packageConfig('stub_path', 'default');
        if ($stub_path == 'default') {
            $stub_path = __DIR__ . '/../stubs/';
        }

        $path = Str::finish($stub_path, '/') . "{$type}.stub";

        if (!$content) {
            return $path;
        }

        return $this->files->get($path);
    }

    /**
     * @param $no
     *
     * @return string
     */
    private function _getSpace($no = 1)
    {
        $tabs = '';
        for ($i = 0; $i < $no; $i++) {
            $tabs .= "\t";
        }

        return $tabs;
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getControllerPath($name)
    {
        return app_path($this->_getNamespacePath($this->controllerNamespace) . "{$name}Controller.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getModelPath($name)
    {
        return $this->makeDirectory(app_path($this->_getNamespacePath($this->modelNamespace) . "{$name}.php"));
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getDataTablePath($name)
    {
        return $this->makeDirectory(app_path($this->_getNamespacePath($this->dataTableNamespace) . "{$name}DataTable.php"));
    }

    /**
     * Get the path from namespace.
     *
     * @param $namespace
     *
     * @return string
     */
    private function _getNamespacePath($namespace)
    {
        $str = Str::start(Str::finish(Str::after($namespace, 'App'), '\\'), '\\');

        return str_replace('\\', '/', $str);
    }

    /**
     * Get the default layout path.
     *
     * @return string
     */
    private function _getLayoutPath()
    {
        return $this->makeDirectory(resource_path("/views/layouts/app.blade.php"));
    }

    /**
     * @param $view
     *
     * @return string
     */
    protected function _getViewPath($view)
    {
        $name = Str::kebab($this->name);

        return $this->makeDirectory(resource_path("/views/{$name}/{$view}.blade.php"));
    }

    /**
     * Build the replacement.
     *
     * @return array
     */
    protected function buildReplacements()
    {
        return [
            '{{layout}}' => $this->layout,
            '{{modelName}}' => $this->name,
            '{{modelTitle}}' => Str::title(Str::snake($this->name, ' ')),
            '{{modelNamespace}}' => $this->modelNamespace,
            '{{dataTableNamespace}}' => $this->dataTableNamespace,
            '{{controllerNamespace}}' => $this->controllerNamespace,
            '{{modelNamePluralLowerCase}}' => Str::camel(Str::plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(Str::plural($this->name)),
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' => $this->options['route'] ?? Str::kebab(Str::plural($this->name)),
            '{{modelView}}' => Str::kebab($this->name),
        ];
    }

    /**
     * Build the form fields for form.
     *
     * @param $title
     * @param $column
     * @param string $type
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function getField($title, $column, $type = 'form-field', $modelRNameRelation = '')
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
            '{{column}}' => $column,
            '{{modelNameRelation}}' => $modelRNameRelation,
        ]);

        return str_replace(
            array_keys($replace), array_values($replace), $this->getStub("views/{$type}")
        );
    }

    /**
     * @param $title
     *
     * @return mixed
     */
    protected function getHead($title)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(10) . '<th>{{title}}</th>' . "\n"
        );
    }

    /**
     * @param $column
     *
     * @return mixed
     */
    protected function getBody($column)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{column}}' => $column,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(11) . '<td>{{ ${{modelNameLowerCase}}->{{column}} }}</td>' . "\n"
        );
    }

    /**
     * Make layout if not exists.
     *
     * @throws \Exception
     */
    protected function buildLayout(): void
    {
        if (!(view()->exists($this->layout))) {

            $this->info('Creating Layout ...');

            if ($this->layout == 'layouts.app') {
                $this->files->copy($this->getStub('layouts/app', false), $this->_getLayoutPath());
            } else {
                throw new \Exception("{$this->layout} layout not found!");
            }
        }
    }

    /**
     * Read package configuration while preserving compatibility with legacy crud.php files.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function packageConfig($key, $default = null)
    {
        $current = config("frex-crud.{$key}");

        if (!is_null($current)) {
            return $current;
        }

        return config("crud.{$key}", $default);
    }

    /**
     * Get the DB Table columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        if (empty($this->tableColumns)) {
            $this->tableColumns = DB::select('SHOW COLUMNS FROM ' . $this->table);
        }

        return $this->tableColumns;
    }

    /**
     * @return array
     */
    protected function getFilteredColumns()
    {
        $unwanted = $this->unwantedColumns;
        $columns = [];

        foreach ($this->getColumns() as $column) {
            $columns[] = $column->Field;
        }

        return array_filter($columns, function ($value) use ($unwanted) {
            return !in_array($value, $unwanted);
        });
    }

    /**
     * Make model attributes/replacements.
     *
     * @return array
     */
    protected function modelReplacements()
    {
        $properties = '*';
        $rulesArray = [];
        $softDeletesNamespace = $softDeletes = '';

        foreach ($this->getColumns() as $value) {
            $properties .= "\n * @property $$value->Field";

            if ($value->Null == 'NO') {
                $rulesArray[$value->Field] = 'required';
            }

            if ($value->Field == 'deleted_at') {
                $softDeletesNamespace = "use Illuminate\Database\Eloquent\SoftDeletes;\n";
                $softDeletes = "use SoftDeletes;\n";
            }
        }

        $rules = function () use ($rulesArray) {
            $rules = '';
            // Exclude the unwanted rulesArray
            $rulesArray = Arr::except($rulesArray, $this->unwantedColumns);
            // Make rulesArray
            foreach ($rulesArray as $col => $rule) {
                $rules .= "\n\t\t'{$col}' => '{$rule}',";
            }

            return $rules;
        };

        $fillable = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "'" . $value . "'";
            });

            // CSV format
            return implode(',', $filterColumns);
        };

        $properties .= "\n *";

        list($relations, $properties) = (new ModelGenerator($this->table, $properties, $this->modelNamespace))->getEloquentRelations();

        return [
            '{{table}}' => $this->table,
            '{{fillable}}' => $fillable(),
            '{{rules}}' => $rules(),
            '{{relations}}' => $relations,
            '{{properties}}' => $properties,
            '{{softDeletesNamespace}}' => $softDeletesNamespace,
            '{{softDeletes}}' => $softDeletes,
        ];
    }


    /**
     * Make model attributes/replacements.
     *
     * @return array
     */
    protected function dataTableReplacements()
    {
        
        $columnArray = [];
       
        foreach ($this->getColumns() as $value) {
                $columnArray[$value->Field] = Str::camel($value->Field);
        }

        $column = function () use ($columnArray) {
            $column = '';
            // Exclude the unwanted columnArray
            $columnArray = Arr::except($columnArray, $this->unwantedColumns);
            // Make columnArray
            foreach ($columnArray as $col => $title) {
                //$column .= "\n\t\t'{$col}' => '{$rule}',";
                $column .= "\n\t\t\tColumn::make('{$col}')->title('{$title}'),";
            }

            return $column;
        };

        $tableRelationsArray = [];
        //print_r($this->options['relation']); exit();
        if($this->options['relation']=="yes"){
            foreach($this->_getTableRelations() as $relation){
                $firstColumn = (new ModelGenerator($this->table, $properties="", $this->modelNamespace))->getFirstColumn($relation->ref_table);
                if($firstColumn!=""){
                    $tableRelationsArray[$firstColumn] = Str::camel(Str::singular($relation->ref_table));
                }
            }
        }


        $tableRelations = "";
        $editColumnRelation = "";
        $columnRelations = "";

        if (count($tableRelationsArray)>0){
            
            foreach($tableRelationsArray as $columnRelation => $relation){

                $editColumnRelation .= "->editColumn('".$relation.".".$columnRelation."', function (\$row) {
                    return \$row->".$relation."->".$columnRelation.";
                })\n";
                $title = Str::title(str_replace('_', ' ', $columnRelation));
                $columnRelations .= "\n\t\t\tColumn::make('".$relation.".".$columnRelation."')->title('".$title."'),";

            }
            $tableRelationsArray = array_values($tableRelationsArray);
            array_walk($tableRelationsArray, function (&$value) {
                $value = "'" . $value . "'";
            });

            $tableRelations = "->with([".implode(',',$tableRelationsArray)."])";

        }
        
        
        return [
            '{{modelNamespace}}' => $this->modelNamespace,
            '{{modelName}}' => $this->name,
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' => $this->options['route'] ?? Str::kebab(Str::plural($this->name)),
            '{{table}}' => $this->table,
            '{{tableRelations}}' => $tableRelations,
            '{{column}}' => $column(),
            '{{editColumnRelation}}' => $editColumnRelation,
            '{{columnRelations}}' => $columnRelations,
        ];
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * Build the options
     *
     * @return $this|array
     */
    protected function buildOptions()
    {
        $route = $this->option('route');
        $relation = $this->option('relation');

        if (!empty($route)) {
            $this->options['route'] = $route;
        }
        if (!empty($relation)) {
            $this->options['relation'] = $relation;
        }else{
            $this->options['relation'] = "yes";
        }

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    /**
     * Is Table exist in DB.
     *
     * @return mixed
     */
    protected function tableExists()
    {
        return Schema::hasTable($this->table);
    }

    /**
     * Get all relations from Table.
     *
     * @return array
     */
    protected function _getTableRelations()
    {
        return (new ModelGenerator($this->table, $properties="", $this->modelNamespace))->_getTableRelations();
    }
}

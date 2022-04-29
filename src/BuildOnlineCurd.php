<?php
// +----------------------------------------------------------------------
// | quickadmin框架 [ quickadmin框架 ]
// +----------------------------------------------------------------------
// | 版权所有 2020~2022 南京新思汇网络科技有限公司
// +----------------------------------------------------------------------
// | 官方网站: https://www.quickadmin.icu
// +----------------------------------------------------------------------
// | Author: zs <909883663@qq.com>
// +----------------------------------------------------------------------

namespace onlinecurd;

use onlinecurd\TableException;
use think\exception\FileException;
use think\facade\Db;
use think\facade\Log;

/**
 * 快速构建系统CURD
 * Class BuildCurd
 *
 */
class BuildOnlineCurd
{

    /**
     * 当前目录
     * @var string
     */
    protected $dir;

    /**
     * 应用目录
     * @var string
     */
    protected $rootDir;

    /**
     * 分隔符
     * @var string
     */
    protected $DS = DIRECTORY_SEPARATOR;

    /**
     * 数据库名
     * @var string
     */
    protected $dbName;

    /**
     *  表前缀
     * @var string
     */
    protected $tablePrefix = 'qu_';

    /**
     * 主表
     * @var string
     */
    protected $table;

    /**
     * 表注释名
     * @var string
     */
    protected $tableComment;

    /**
     * 主表列信息
     * @var array
     */
    protected $tableColumns;

    /**
     * 数据列表可见字段
     * @var string
     */
    protected $fields;

    /**
     * 是否软删除模式
     * @var bool
     */
    protected $delete = false;
    /**
     * 软删除标识
     */
    protected $deleteTime = 'delete_time';
    /**
     * 是否强制覆盖
     * @var bool
     */
    protected $force = false;

    /**
     * model放的位置
     */
    protected $modelPath = "";

    /**
     * 后台模块
     */
    protected $back_module = "";
    /**
     * 关联模型
     * @var array
     */
    protected $relationArray = [];

    /**
     * 控制器对应的URL
     * @var string
     */
    protected $controllerUrl;

    /**
     * 生成的控制器名
     * @var string
     */
    protected $controllerFilename;

    /**
     * 控制器命名
     * @var string
     */
    protected $controllerName;

    /**
     * 控制器命名空间
     * @var string
     */
    protected $controllerNamespace;

    /**
     * 视图名
     * @var string
     */
    protected $viewFilename;

    /**
     * js文件名
     * @var string
     */
    protected $jsFilename;

    /**
     * 生成的模型文件名
     * @var string
     */
    protected $modelFilename;

    /**
     * 主表模型命名
     * @var string
     */
    protected $modelName;

    /**
     * 外键
     * @var array
     */
    protected $foreigns = [];

    /**
     * 相关生成文件
     * @var array
     */
    protected $fileList = [];
    /**
     * 必填项目
     */
    protected $requiredJs;

    /**
     * 关联关系
     */
    protected $relations = [];

    /**
     * 主表字段
     */
    protected $fieldlist = "";

    /**
     * 编辑表单不显示字段
     * @var array
     */
    protected $ignoreFields = ['create_time', 'update_time', 'delete_time'];

    /**
     * 查询排除的类型
     * @var array
     */
    protected $queryIgnoreType = ['file', 'files', 'selects', 'editor', 'textarea', 'checkbox'];

    /**
     * 单图片字段后缀
     * @var array
     */
    protected $imageFieldSuffix = ['image', 'logo', 'photo', 'icon'];

    /**
     * 多图片字段后缀
     * @var array
     */
    protected $imagesFieldSuffix = ['images', 'photos', 'icons'];

    /**
     * 单文件字段后缀
     * @var array
     */
    protected $fileFieldSuffix = ['file'];

    /**
     * 多文件字段后缀
     * @var array
     */
    protected $filesFieldSuffix = ['files'];

    /**
     * 时间字段后缀
     * @var array
     */
    protected $dateFieldSuffix = ['time', 'date'];

    /**
     * 开关组件字段
     * @var array
     */
    protected $switchFields = ['switch'];

    /**
     * 富文本字段
     * @var array
     */
    protected $editorFields = [];

    /**
     * 排序字段
     * @var array
     */
    protected $sortFields = [];

    /**
     * 表单类型
     * @var array
     */
    protected $formTypeArray = ['text', 'image', 'images', 'file', 'files', 'select', 'selects', 'switch', 'date', 'editor', 'textarea', 'checkbox', 'radio', 'inputnumber'];

    /**
     * 系统表, crud不会生成
     */
    protected $systemTables = [
        'system_admin', 'system_log', 'system_menu', 'system_config', 'system_group', 'system_group_admin', 'system_group_menu', 'system_onlinecurd',
    ];
    /**
     * 验证规则
     */
    protected $validateList = [];
    /**
     * 编辑场景
     */
    protected $editField = "";

    /**
     * 初始化
     * BuildCurd constructor.
     */
    public function __construct()
    {
        $this->tablePrefix = config('database.connections.mysql.prefix');
        $this->dbName = config('database.connections.mysql.database');
        $this->dir = __DIR__;
        $this->rootDir = root_path();
        return $this;
    }

    /**
     * 设置主表
     * @param $table
     * @return $this
     * @throws TableException
     */
    public function setTable($table)
    {
        $this->table = $this->getTableNameExpPrefix($table);

        // 初始化默认控制器名
        $nodeArray = explode('_', $this->table);
        if (count($nodeArray) == 1) {
            $this->controllerFilename = ucfirst($nodeArray[0]);
        } else {
            foreach ($nodeArray as $k => $v) {
                if ($k == 0) {
                    $this->controllerFilename = "{$v}{$this->DS}";
                } else {
                    $this->controllerFilename .= ucfirst($v);
                }
            }
        }
        $nodeArray = explode($this->DS, $this->controllerFilename);
        $formatArray = [];
        foreach ($nodeArray as $vo) {
            $formatArray[] = $this->humpToLine(lcfirst($vo));
        }
        $this->controllerUrl = implode('.', $formatArray);
        $this->viewFilename = implode($this->DS, $formatArray);
        $this->jsFilename = $this->viewFilename;

        // 控制器命名空间
        $namespaceArray = $nodeArray;
        $this->controllerName = array_pop($namespaceArray);
        $namespaceSuffix = implode('\\', $namespaceArray);

        $this->controllerNamespace = empty($namespaceSuffix) ? "app\\{$this->back_module}\controller" : "app\\{$this->back_module}\controller\\{$namespaceSuffix}";

        // 初始化默认模型名
        $this->modelFilename = ucfirst($this->lineToHump($this->table));
        // 主表模型命名
        $modelArray = explode($this->DS, $this->modelFilename);
        $this->modelName = array_pop($modelArray);

        return $this;
    }

    /**
     * 设置关联表
     * @param $relationTable
     * @param $foreignKey
     * @param null $primaryKey
     * @param null $modelFilename
     * @param array $onlyShowFileds
     * @param null $bindSelectField
     * @return $this
     * @throws TableException
     */
    public function setRelation($relationTable, $foreignKey, $primaryKey = null, $modelFilename = null, $onlyShowFileds = [], $bindSelectField = null)
    {
        if (!isset($this->tableColumns[$foreignKey])) {
            throw new TableException("主表不存在外键字段：{$foreignKey}");
        }
        if (!empty($modelFilename)) {
            $modelFilename = str_replace('/', $this->DS, $modelFilename);
        }
        try {
            $sql = "SELECT * FROM `information_schema`.`columns` "
                . "WHERE TABLE_SCHEMA = ? AND table_name = ? "
                . "ORDER BY ORDINAL_POSITION";
            //加载主表的列
            $colums = Db::query($sql, [$this->dbName, $this->tablePrefix . $relationTable]);
            $formatColums = [];
            $delete = false;
            if (!empty($bindSelectField) && !in_array($bindSelectField, array_column($colums, 'Field'))) {
                throw new TableException("关联表{$relationTable}不存在该字段: {$bindSelectField}");
            }
            foreach ($colums as $vo) {
                if (empty($primaryKey) && $vo['COLUMN_KEY'] == 'PRI') {
                    $primaryKey = $vo['COLUMN_NAME'];
                }
                if (!empty($onlyShowFileds) && !in_array($vo['COLUMN_NAME'], $onlyShowFileds)) {
                    continue;
                }
                $colum = [
                    'type' => $vo['COLUMN_TYPE'],
                    'comment' => !empty($vo['COLUMN_COMMENT']) ? $vo['COLUMN_COMMENT'] : $vo['COLUMN_NAME'],
                    'required' => $vo['IS_NULLABLE'] == "YES" ? true : false,
                    'default' => $vo['COLUMN_DEFAULT'],
                    'data_type' => $vo['DATA_TYPE'],
                    'field' => $vo['COLUMN_NAME'],
                    'NUMERIC_SCALE' => $vo['NUMERIC_SCALE'],
                ];
                $this->buildColum($colum);

                $formatColums[$vo['COLUMN_NAME']] = $colum;
                if ($vo['COLUMN_NAME'] == $this->deleteTime) {
                    $delete = true;
                }
            }

            $modelFilename = empty($modelFilename) ? ucfirst($this->lineToHump($relationTable)) : $modelFilename;
            $modelArray = explode($this->DS, $modelFilename);
            $modelName = array_pop($modelArray);

            $relation = [
                'modelFilename' => $modelFilename,
                'modelName' => $modelName,
                'foreignKey' => $foreignKey,
                'primaryKey' => $primaryKey,
                'bindSelectField' => $bindSelectField,
                'delete' => $delete,
                'tableColumns' => $formatColums,
            ];
            if (!empty($bindSelectField)) {
                $relationArray = explode('\\', $modelFilename);
                $this->tableColumns[$foreignKey]['bindSelectField'] = $bindSelectField;
                $this->tableColumns[$foreignKey]['bindRelation'] = end($relationArray);
            }
            $this->relationArray[$relationTable] = $relation;
            $this->selectFileds[] = $foreignKey;
            $this->foreigns[$foreignKey] = $relationTable;
        } catch (\Exception $e) {
            throw new TableException($e->getMessage());
        }
        return $this;
    }

    /**
     * 设置表备注名称
     */
    public function setTableComment($tabel_comment)
    {
        $this->tableComment = $tabel_comment;
        return $this;
    }

    /**
     * 设置是否强制替换
     * @param $force
     * @return $this
     */
    public function setForce($force)
    {
        $this->force = $force;
        return $this;
    }
    /**
     * 设置model放的的位置
     */
    public function setModelPath($modelPath)
    {
        $this->modelPath = $modelPath;
        return $this;
    }
    /**
     * 主表字段集
     */
    public function setFieldlist($fieldlist)
    {
        $this->fieldlist = $fieldlist;
        return $this;
    }

    public function setRelations($relations)
    {
        $this->relations = $relations;
        foreach ($relations as $val) {
            $this->foreigns[$val['foreign_key']] = $val['table_name'];
        }
        return $this;
    }

    /**
     * 设置后端模块
     */
    public function setBackModule($back_module)
    {
        $this->back_module = $back_module;
        return $this;
    }

    /**
     * 获取相关的文件
     * @return array
     */
    public function getFileList()
    {
        return $this->fileList;
    }

    protected function buildRequiredJs($require, $field, $comment)
    {
        $rjs = "";
        if ($require) {
            $rjs = "        $field: [{ required: true, message: \"请输入$comment\", trigger: \"blur\" }],
        ";
        }
        return $rjs;
    }
    /***
     * 构建columns
     */
    protected function buildColoumsJs($field, $comment, $component = '', $formatter = '')
    {
        $componentString = '';
        $formatterString = '';
        if ($component) {
            $componentString = "component:'$component',";
        }
        if ($formatter) {
            $formatterString = <<<EOD
formatter: (prop, row) => {
                        $formatter
                    },
EOD;
        }
        if ($componentString && $formatterString) {
            $coloumsJs = <<<EOD
                {
                    visible: true,
                    label: '$comment',
                    prop: '$field',
                    $componentString
                    $formatterString
                },

EOD;
            return $coloumsJs;
        }

        if (!$componentString && !$formatterString) {
            $coloumsJs = <<<EOD
                {
                    visible: true,
                    label: '$comment',
                    prop: '$field',
                },

EOD;
            return $coloumsJs;
        }

        if (!$componentString && $formatterString) {
            $coloumsJs = <<<EOD
                {
                    visible: true,
                    label: '$comment',
                    prop: '$field',
                    $formatterString
                },

EOD;
            return $coloumsJs;
        }
        if ($componentString && !$formatterString) {
            $coloumsJs = <<<EOD
                {
                    visible: true,
                    label: '$comment',
                    prop: '$field',
                    $componentString
                },

EOD;
            return $coloumsJs;
        }
    }
    /**
     * 构建查询参数
     */
    protected function buildQueryParams($field, $value, $op)
    {
        $queryParams = <<<EOD
                 $field: { value:$value, op: '$op'},

 EOD;
        return $queryParams;
    }

    /**
     * 构建查询页面
     */
    protected function buildQueryhtml($queryHtml, $searchExpand = 0)
    {
        $queryString = $searchExpand == 8 ? '                    <template v-if="searchExpand">
        ' : "";

        $queryString .= <<<EOD
                     <el-col :md="6" :sm="12">
                         {$queryHtml}
                     </el-col>

 EOD;
        return $queryString;
    }
    /**
     * 构建查询页面
     */
    protected function buildFormData($field)
    {

        return $field . ": [],";
    }
    /**
     * 构建data
     * @param $require
     * @return string
     */
    protected function buildData($field, $array)
    {
        $dataString = "";
        foreach ($array as $k => $v) {
            $dataString .= "
            " . "   { label: \"$v\", value: $k },";
        }
        $dataString = <<<EOD
            {$field}:[{$dataString}
            ],

EOD;
        return $dataString;

    }

    /**
     * 构建初始化字段信息
     * @param $colum
     * @return mixed
     */
    protected function buildColum(&$colum)
    {

        $string = $colum['comment'];
        $comment = $string;
        // 处理定义类型
        preg_match('/\([\s\S]*?\)/i', $string, $formTypeMatch);
        $formatDefine = [];
        if (!empty($formTypeMatch) && isset($formTypeMatch[0])) {
            $formType = trim(str_replace(')', '', str_replace('(', '', $formTypeMatch[0])));
            if (in_array($formType, $this->formTypeArray)) {
                $colum['form_type'] = $formType;
            }
            if (isset($colum['form_type']) && in_array($colum['form_type'], ['select', 'selects', 'radio', 'checkbox', 'switch'])) {

                $arr = explode(":", $string);
                if (isset($arr[1])) {
                    $optionArr = explode(",", $arr[1]);
                    if ($optionArr) {
                        foreach ($optionArr as $k => $v) {
                            $dataOption = explode("=", $v);
                            if (isset($dataOption[0]) && isset($dataOption[1])) {
                                $formatDefine[trim($dataOption[0])] = trim($dataOption[1]);
                            }
                        }
                    }

                }
                if (isset($arr[0])) {
                    $comment = $arr[0];
                    $comment = str_replace("(selects)", '', $comment);
                    $comment = str_replace("(select)", '', $comment);
                    $comment = str_replace("(radio)", '', $comment);
                    $comment = str_replace("(checkbox)", '', $comment);
                    $comment = str_replace("(switch)", '', $comment);
                }
            }
            $colum['comment'] = $comment;
        }
        $colum['comment'] = trim($colum['comment']);
        $colum['dict'] = (object) $formatDefine;
        return $colum;
    }

    /**
     * 构架下拉模型
     * @param $field
     * @param $array
     * @return mixed
     */
    protected function buildSelectModel($field, $array)
    {
        $field = $this->lineToHump(ucfirst($field));
        $name = "get{$field}List";
        $values = '[';
        foreach ($array as $k => $v) {
            $values .= "'{$k}'=>'{$v}',";
        }
        $values .= ']';
        $selectCode = $this->replaceTemplate(
            $this->getTemplate("model{$this->DS}select"),
            [
                'name' => $name,
                'values' => $values,
            ]);
        return $selectCode;
    }

    /**
     * 构架textattr模型
     * @param $field
     * @param $array
     * @return mixed
     */
    protected function buildTextAttrModel($field)
    {
        $name = $field;
        $nameUpper = $this->lineToHump(ucfirst($field));

        $textAttrCode = $this->replaceTemplate(
            $this->getTemplate("model{$this->DS}textAttr"),
            [
                'name' => $name,
                'nameUpper' => $nameUpper,
            ]);
        return $textAttrCode;
    }

    /**
     * 构架textattrs模型
     * @param $field
     * @param $array
     * @return mixed
     */
    protected function buildTextAttrsModel($field)
    {
        $name = $field;
        $nameUpper = $this->lineToHump(ucfirst($field));

        $textAttrCode = $this->replaceTemplate(
            $this->getTemplate("model{$this->DS}textAttrs"),
            [
                'name' => $name,
                'nameUpper' => $nameUpper,
            ]);
        return $textAttrCode;
    }

    /**
     * 初始化
     * @return $this
     */
    public function render()
    {

        // 控制器
        $this->renderController();
        // 模型
        $this->renderModel();
        // 视图
        $this->renderView();

        $this->renderValidate();
        return $this;
    }

    /**
     * 获取字段类型
     */

    protected function getFieldType($val)
    {

        $fieldType = "input";

        switch ($val['DATA_TYPE']) {
            case 'bigint':
            case 'int':
            case 'mediumint':
            case 'smallint':
            case 'tinyint':
            case 'decimal':
            case 'double':
            case 'float':
                $fieldType = 'inputnumber';
                break;
            case 'longtext':
            case 'text':
            case 'mediumtext':
            case 'smalltext':
            case 'tinytext':
                $fieldType = 'textarea';
                break;
            case 'year':
            case 'date':
            case 'time':
            case 'datetime':
            case 'timestamp':
                $fieldType = 'datetime';
                break;
            default:
                break;
        }

        if ($this->checkContain($val['COLUMN_NAME'], $this->imagesFieldSuffix)) {
            return 'images';
        }
        // 判断图片
        if ($this->checkContain($val['COLUMN_NAME'], $this->imageFieldSuffix)) {
            return 'image';
        }
        if ($this->checkContain($val['COLUMN_NAME'], $this->filesFieldSuffix)) {
            return 'files';
        }
        // 判断文件
        if ($this->checkContain($val['COLUMN_NAME'], $this->fileFieldSuffix)) {
            return 'file';
        }

        // 判断时间
        if ($this->checkContain($val['COLUMN_NAME'], $this->dateFieldSuffix)) {
            return 'date';
        }

        // 判断开关
        if (in_array($val['COLUMN_NAME'], $this->switchFields)) {
            return 'switch';
        }

        // 判断富文本
        if (in_array($val['COLUMN_NAME'], $this->editorFields)) {
            return 'editor';
        }

        // 判断排序
        if (in_array($val['COLUMN_NAME'], $this->sortFields)) {
            return 'sort';
        }

        return $fieldType;
    }

    protected function getQueryType($field_type)
    {
        $query_type = "=";
        switch ($field_type) {
            case "input":
            case "textarea":
                $query_type = "%like%";
                break;
            case "date":
                $query_type = "range";
                break;
            default:
                $query_type = "=";
                break;
        }
        return $query_type;
    }

    /**
     * 初始化控制器
     * @return $this
     */
    protected function renderController()
    {

        $controllerFile = "{$this->rootDir}app{$this->DS}{$this->back_module}{$this->DS}controller{$this->DS}{$this->controllerFilename}.php";

        $relationSearch = "false";

        if (empty($this->relations)) {
            $controllerIndexMethod = '';
            $usePackage = '';
        } else {
            $relationSearch = "true";
            $relationCode = '';
            $relationWith = [];
            $relationField = ''; //关联表字段
            foreach ($this->relations as $key => $val) {
                $relation = $this->lineToHump($this->getTableNameExpPrefix($val['table_name']));
                $relationWith[] = $relation;

                $tableColumns = "'" . $relation . "'=>[";
                foreach ($val['fieldlist'] as $v) {
                    $tableColumns .= "'" . $v['field'] . "',";
                }
                $tableColumns .= "],";
                $relationField .= $tableColumns;
            }
            $relationCode = "->withJoin(['" . implode("','", $relationWith) . "'],'LEFT')";
            $controllerIndexMethod = $this->replaceTemplate(
                $this->getTemplate("controller{$this->DS}indexMethod"),
                [
                    'relationIndexMethod' => $relationCode,
                    'relationField' => $relationField,
                ]);
            $usePackage = 'use think\facade\Db;
use util\CommonTool;
use util\Excel;';
        }

        $modelFilenameExtend = str_replace($this->DS, '\\', $this->modelFilename);
        $modelPath = $this->modelPath;
        $controllerValue = $this->replaceTemplate(
            $this->getTemplate("controller{$this->DS}controller"),
            [
                'controllerName' => $this->controllerName,
                'controllerNamespace' => $this->controllerNamespace,
                'controllerAnnotation' => $this->tableComment,
                'modelFilename' => "\app\\$modelPath\model\\{$modelFilenameExtend}",
                'validateFilename' => "\app\\{$this->back_module}\\validate\\{$modelFilenameExtend}",
                'indexMethod' => $controllerIndexMethod,
                'usePackage' => $usePackage,
                'relationSearch' => $relationSearch,
            ]);
        $this->fileList[$controllerFile] = $controllerValue;
        return $this;
    }

    protected function renderValidate()
    {

        $validateFile = "{$this->rootDir}app{$this->DS}{$this->back_module}{$this->DS}validate{$this->DS}{$this->modelFilename}.php";
        $extendNamespaceArray = explode($this->DS, $this->modelFilename);
        $extendNamespace = null;
        if (count($extendNamespaceArray) > 1) {
            array_pop($extendNamespaceArray);
            $extendNamespace = '\\' . implode('\\', $extendNamespaceArray);
        }
        $validateList = $this->validateList;
        $rules = "";
        foreach ($validateList as $k => $v) {
            //$rules.="'".$k."'=>'".$v."',";
            $rules .= "
        " . <<<EOD
            '{$k}'=>'{$v}',
            EOD;
        }
        $validateValue = $this->replaceTemplate(
            $this->getTemplate("model{$this->DS}validate"),
            [
                'validateName' => $this->modelName,
                'validateNamespace' => "app\\{$this->back_module}\\validate{$extendNamespace}",
                'rules' => $rules,
                'editfields' => $this->editField,
            ]);
        $this->fileList[$validateFile] = $validateValue;

    }
    /**
     * 初始化模型
     * @return $this
     */
    protected function renderModel()
    {
        $modelPath = $this->modelPath;
        // 主表模型
        $modelFile = "{$this->rootDir}app{$this->DS}{$modelPath}{$this->DS}model{$this->DS}{$this->modelFilename}.php";
        if (empty($this->relations)) {
            $relationList = '';
        } else {
            $relationList = '';
            foreach ($this->relations as $key => $val) {
                $relation_type = $val['relation_type'];
                if ($relation_type == "belong_to") {
                    $relation_type = "belongsTo";
                } else {
                    $relation_type = "hasOne";
                }
                $relationTable = $this->getTableNameExpPrefix($val['table_name']);
                $relation = $this->lineToHump($relationTable);
                $relationTable = ucfirst($relation);
                $relationCode = $this->replaceTemplate(
                    $this->getTemplate("model{$this->DS}relation"),
                    [
                        'relationMethod' => $relation,
                        'relationModel' => "\app\\$modelPath\model\\{$relationTable}",
                        'foreignKey' => $val['foreign_key'],
                        'primaryKey' => $val['primary_key'],
                        'relationType' => $relation_type,
                    ]);
                $relationList .= $relationCode;
            }
        }

        $selectList = '';
        $textAttr = '';
        $append = '';
        $delete = false; //软删除
        foreach ($this->fieldlist as $val) {
            if (isset($val['form_type']) && in_array($val['form_type'], ['select', 'selects', 'switch', 'radio', 'checkbox']) && isset($val['dict'])) {
                $selectList .= $this->buildSelectModel($val['field'], $val['dict']);
                $append .= "
                '" . $val['field'] . "_text'" . ", ";
            }
            if (isset($val['form_type']) && in_array($val['form_type'], ['select', 'switch', 'radio']) && isset($val['dict'])) {
                $textAttr .= $this->buildTextAttrModel($val['field']);
            }
            if (isset($val['form_type']) && in_array($val['form_type'], ['selects', 'checkbox']) && isset($val['dict'])) {
                $textAttr .= $this->buildTextAttrsModel($val['field']);
            }

            //验证器
            if ($val['is_required'] && !$val['is_primary']) {
                $this->validateList[$val['field']] = "require";
            }
            if ($val['valid_type'] && !$val['is_primary']) {
                $this->validateList[$val['field']] = isset($this->validateList[$val['field']]) ? $this->validateList[$val['field']] . "|" . $val['valid_type'] : $val['valid_type'];
            }
            if ($val['valid_type'] && $val['edit_show'] && $val['edit_only_read'] == false) {
                $this->editField .= "'" . $val['field'] . "',";
            }

        }
        $sql_delete = "describe `{$this->tablePrefix}{$this->table}` `{$this->deleteTime}`";
        //加载主表的列
        $delete_colum = Db::query($sql_delete);
        if ($delete_colum) {
            $delete = true;
        }

        $extendNamespaceArray = explode($this->DS, $this->modelFilename);
        $extendNamespace = null;
        if (count($extendNamespaceArray) > 1) {
            array_pop($extendNamespaceArray);
            $extendNamespace = '\\' . implode('\\', $extendNamespaceArray);
        }

        $modelValue = $this->replaceTemplate(
            $this->getTemplate("model{$this->DS}model"),
            [
                'modelName' => $this->modelName,
                'modelNamespace' => "app\\$modelPath\model{$extendNamespace}",
                'modelPath' => $modelPath,
                'table' => $this->table,
                'deleteTime' => $delete ? '"' . $this->deleteTime . '"' : 'false',
                'useSoftDelete' => $delete ? 'use think\model\concern\SoftDelete;' : '',
                'useSoftDeleteTrait' => $delete ? 'use softDelete;' : '',
                'relationList' => $relationList,
                'selectList' => $selectList,
                'textAttr' => $textAttr,
                'append' => $append,
            ]);
        $this->fileList[$modelFile] = $modelValue;

        // 关联模型
        foreach ($this->relations as $val) {
            $table_name = $this->getTableNameExpPrefix($val['table_name']);
            $modelFilename = ucfirst($this->lineToHump($table_name));
            // 主表模型命名
            $modelArray = explode($this->DS, $modelFilename);
            $modelName = array_pop($modelArray);

            $relationModelFile = "{$this->rootDir}app{$this->DS}{$modelPath}{$this->DS}model{$this->DS}{$modelName}.php";

            // todo 判断关联模型文件是否存在, 存在就不重新生成文件, 防止关联模型文件被覆盖
            $relationModelClass = "\\app\\$modelPath\\model\\{$modelName}";
            if (class_exists($relationModelClass) && method_exists(new $relationModelClass, 'getName')) {
                $tableName = (new $relationModelClass)->getName();
                if ($this->humpToLine(lcfirst($tableName)) == $this->humpToLine(lcfirst($modelName))) {
                    continue;
                }
            }

            $sql_delete1 = "describe `{$val['table_name']}` `{$this->deleteTime}`";
            //加载主表的列
            $delete_colum1 = Db::query($sql_delete1);
            if ($delete_colum1) {
                $val['delete'] = true;
            }

            $extendNamespaceArray = explode($this->DS, $modelFilename);
            $extendNamespace = null;
            if (count($extendNamespaceArray) > 1) {
                array_pop($extendNamespaceArray);
                $extendNamespace = '\\' . implode('\\', $extendNamespaceArray);
            }

            $relationModelValue = $this->replaceTemplate(
                $this->getTemplate("model{$this->DS}model"),
                [
                    'modelName' => $modelName,
                    'modelPath' => $modelPath,
                    'modelNamespace' => "app\\$modelPath\model{$extendNamespace}",
                    'table' => $table_name,
                    'deleteTime' => isset($val['delete']) ? '"' . $this->deleteTime . '"' : 'false',
                    'useSoftDelete' => isset($val['delete']) ? 'use think\model\concern\SoftDelete;' : '',
                    'useSoftDeleteTrait' => isset($val['delete']) ? 'use softDelete;' : '',
                    'relationList' => '',
                    'selectList' => '',
                    'textAttr' => '',
                    'append' => '',
                ]);

            $this->fileList[$relationModelFile] = $relationModelValue;
        }
        return $this;
    }
    /**
     * 获取coloumnjs
     */
    public function getColoumsJs($formType, $field, $comment, $dict)
    {
        $coloumsJsTemp = $this->buildColoumsJs($field, $comment);
        if ($formType == 'image') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminImage');
        } elseif ($formType == 'images') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminImage');
        } elseif ($formType == 'file') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminPopover');
        } elseif ($formType == 'files') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminPopover');
        } elseif ($formType == 'editor') {
            $coloumsJsTemp = '';
        } elseif ($formType == 'switch') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminSwitch');
        } elseif ($formType == 'date') {

            $coloumsJsTemp = $this->buildColoumsJs($field, $comment);
        } elseif ($formType == 'radio') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, '', "return  row." . $field . "_text");
        } elseif ($formType == 'checkbox') {
            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminTag', "return  row." . $field . "_text");
        } elseif ($formType == 'selects') {

            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, 'QuickAdminTag', "return  row." . $field . "_text");
        } elseif ($formType == 'select') {
            $component = "";
            $rowstring = "row." . $field . "_text";
            $formatterString = "return  $rowstring";
            if ($dict) {
                $component = "QuickAdminTextColor";
                $caseString = "";
                foreach ($dict as $k => $v) {
                    $type = "";
                    switch ($k) {
                        case 0:
                            $type = "danger";
                            break;
                        case 1:
                            $type = "success";
                            break;
                        case 2:
                            $type = "warning";
                            break;
                        case 3:
                            $type = "info";
                            break;
                        default:
                            $type = "primary";
                            break;
                    }
                    $caseString .= <<<EOD
                                case $k:
                                    type = '$type'
                                    break

    EOD;

                }
                $formatterString = <<<EOD
let type = ''
                        switch (prop) {
$caseString
                        }
                        return {
                            value: prop,
                            type: type,
                            text: $rowstring,
                        }
EOD;
            }

            $coloumsJsTemp = $this->buildColoumsJs($field, $comment, $component, $formatterString);
        }
        return $coloumsJsTemp;
    }
    /**
     * 初始化视图
     * @return $this
     */
    protected function renderView()
    {

        $pathArr = explode($this->DS, $this->rootDir);
        array_pop($pathArr);
        array_pop($pathArr);
        $pathFront = implode($this->DS, $pathArr);
        // 列表页面
        $frontBase = config('app.front');
        $frontDir = "{$pathFront}{$this->DS}{$frontBase}";
        if (!is_dir($frontDir)) {
            throw new FileException('请确保前端目录和后台目录在一个文件夹下！');
        }
        $viewIndexFile = "{$pathFront}{$this->DS}{$frontBase}{$this->DS}src{$this->DS}views{$this->DS}{$this->viewFilename}{$this->DS}index.vue";

        // 编辑页面
        $viewEditFile = "{$pathFront}{$this->DS}{$frontBase}{$this->DS}src{$this->DS}views{$this->DS}{$this->viewFilename}{$this->DS}EditFormCom.vue";
        $editFormList = '';
        $queryFormList = ''; //查询html
        $queryLength = 0; //查询字段数量
        $queryFormJs = ''; //查询js
        $requireJs = ""; //必填字段js
        $coloumsJs = ""; //列表column
        $formData = ''; //formdata;

        $data = "";
        $indexData = ""; //
        foreach ($this->fieldlist as $val) {
            $field = $val['field'];
            $comment = $val['comment'];
            $formType = $val['form_type'];
            // $coloumsJsTemp = $this->buildColoumsJs($field, $comment);
            $step = 1;
            $templateFile = "view{$this->DS}module{$this->DS}input";
            // 根据formType去获取具体模板
            $templateFile = "view{$this->DS}module{$this->DS}{$formType}";
            if ($formType == 'inputnumber') {
                $step = $val['numeric_scale'] > 0 ? "0." . str_repeat(0, $val['numeric_scale'] - 1) . "1" : 1;
            }
            if ($formType == 'checkbox') {
                $formData .= $this->buildFormData($field);
            }
            $dict = isset($val['dict']) ? $val['dict'] : [];
            if (in_array($formType, ['checkbox', 'radio', 'select', 'selects'])) {
                $data .= $this->buildData($field, $dict);
            }
            $coloumsJsTemp = $val['index_show'] ? $this->getColoumsJs($formType, $field, $comment, $dict) : '';
            $relation = '';
            if (isset($this->foreigns[$field])) {
                $templateFile = "view{$this->DS}module{$this->DS}selectPage";
                $subTable = $this->getControllerUrl($this->foreigns[$field]);
                $relation = $subTable;
                $formType = "selectPage";
            }
            $coloumsJs .= $coloumsJsTemp;
            if ($val['is_query']) {
                $templateFileQuery = $templateFile;
                if (in_array($formType, ['switch', 'radio', 'selects', 'checkbox'])) {
                    $templateFileQuery = "view{$this->DS}module{$this->DS}select";
                }
                if ($formType == 'date') {
                    $templateFileQuery = "view{$this->DS}module{$this->DS}daterange";
                }
                if (in_array($formType, ['inputnumber', 'file', 'files', 'image', 'images', 'editor', 'switch', 'textarea'])) {
                    $templateFileQuery = "view{$this->DS}module{$this->DS}input";
                }

                $queryHtml = $this->replaceTemplate(
                    $this->getTemplate($templateFileQuery),
                    [
                        'comment' => $comment,
                        'field' => $field,
                        'prefix' => 'queryParams',
                        'suffix' => '.value',
                        'relation' => $relation,
                        'space' => '        ', //空格数量
                    ]);
                $queryLength++;
                $queryFormList .= $this->buildQueryhtml($queryHtml, $queryLength);
                $queryFormJsTemp = $this->buildQueryParams($field, 'undefined', $val['query_type']);
                if ($formType == 'date') {
                    $queryFormJsTemp = $this->buildQueryParams($field, '[]', "range");
                }
                $queryFormJs .= $queryFormJsTemp;
            }
            //edit表单
            if (in_array($field, ['id', 'create_time']) || in_array($field, $this->ignoreFields)) {
                continue;
            }
            $editFormElement = $this->replaceTemplate(
                $this->getTemplate($templateFile),
                [
                    'comment' => $val['comment'],
                    'field' => $field,
                    'prefix' => 'form',
                    'suffix' => '',
                    'relation' => $relation,
                    'step' => $step,
                    'space' => '', //空格数量
                ]);
            $editFormList .= <<<EOD
                {$editFormElement}

EOD;
            $requireJs .= $this->buildRequiredJs($val['is_required'], $field, $val['comment']);

        }
        $indexData = $data;
        // 关联表
        foreach ($this->relations as $table => $tableVal) {
            $table_name = $this->getTableNameExpPrefix($tableVal['table_name']);
            $table = $this->lineToHump($table_name);
            $queryRelationFormJs = ""; //关联查询js
            $selectData = ""; //select里数据
            foreach ($tableVal['fieldlist'] as $val) {
                $field = $val['field'];
                $comment = isset($val['comment']) ? $val['comment'] : $field;
                $formType = isset($val['form_type']) ? $val['form_type'] : 'input';
                $relationField = $table . '.' . $field;

                $templateFile = "view{$this->DS}module{$this->DS}input";
                // 根据formType去获取具体模板
                $templateFile = "view{$this->DS}module{$this->DS}{$formType}";
                // 根据formType去获取具体模板
                if ($formType == 'checkbox') {
                    $formData .= $this->buildFormData($relationField);
                }
                $dict = isset($val['dict']) ? $val['dict'] : [];
                if (in_array($formType, ['checkbox', 'radio', 'select', 'selects'])) {
                    $selectData .= $this->buildData($field, $dict);
                }
                $coloumsJsTemp = $this->getColoumsJs($formType, $relationField, $comment, $dict);

                $coloumsJs .= $coloumsJsTemp;
                if ($val['is_query']) {
                    $templateFileQuery = $templateFile;
                    if (in_array($formType, ['radio', 'selects', 'checkbox'])) {
                        $templateFileQuery = "view{$this->DS}module{$this->DS}select";
                    }
                    if ($formType == 'date') {
                        $templateFileQuery = "view{$this->DS}module{$this->DS}daterange";
                    }
                    if ($formType == 'inputnumber') {
                        $templateFileQuery = "view{$this->DS}module{$this->DS}input";
                    }
                    if ($templateFileQuery) {
                        $queryHtml = $this->replaceTemplate(
                            $this->getTemplate($templateFileQuery),
                            [
                                'comment' => $comment,
                                'field' => $relationField,
                                'prefix' => 'queryParams',
                                'suffix' => '.value',
                                'relation' => $relation,
                                'space' => '        ', //空格数量
                            ]);
                        $queryLength++;
                        $queryFormList .= $this->buildQueryhtml($queryHtml, $queryLength);

                    }

                    $queryRelationFormJsTemp = $this->buildQueryParams($field, 'undefined', "=");
                    if ($formType == 'date') {
                        $queryRelationFormJsTemp = $this->buildQueryParams($field, '[]', "range");
                    }
                    $queryRelationFormJs .= $queryRelationFormJsTemp;
                }

                //edit表单
                if (in_array($field, ['id', 'create_time']) || in_array($field, $this->ignoreFields)) {
                    continue;
                }
            }

            if ($queryRelationFormJs) {
                $queryFormJs .= <<<EOD
                            {$table}:{ {$queryRelationFormJs}
                            },
            EOD;
            }
            if ($selectData) {
                $indexData .= <<<EOD
                            {$table}:{ {$selectData}
                            },
            EOD;
            }

        }

        //edit页面渲染
        $viewEditValue = $this->replaceTemplate(
            $this->getTemplate("view{$this->DS}form"),
            [
                'formList' => $editFormList,
                'requireJs' => $requireJs,
                'data' => $data,
                'formData' => $formData,
            ]);
        $this->fileList[$viewEditFile] = $viewEditValue;
        //index页面渲染
        $expandHtml = "";
        if ($queryLength > 7) {
            $queryFormList .= "
                    </template>";
            $expandHtml = <<<EOD
                            <el-link @click="searchExpand = !searchExpand" type="primary" :underline="false" class="ml0">
                                <template v-if="searchExpand"> 收起<i class="el-icon-arrow-up"></i> </template>
                                <template v-else> 展开<i class="el-icon-arrow-down"></i> </template>
                            </el-link>
EOD;
        }
        $viewIndexValue = $this->replaceTemplate(
            $this->getTemplate("view{$this->DS}index"),
            [
                'backModule' => $this->back_module,
                'controllerUrl' => $this->controllerUrl,
                'controllerName' => $this->controllerName,
                'coloumsJs' => $coloumsJs,
                'queryForm' => $queryFormList,
                'queryFormJs' => $queryFormJs,
                'data' => $indexData,
                'expandHtml' => $expandHtml,
            ]);
        $this->fileList[$viewIndexFile] = $viewIndexValue;

        return $this;
    }

    /**
     * 检测文件
     * @return $this
     */
    protected function check()
    {
        if (in_array($this->table, $this->systemTables)) {
            throw new TableException("系统表，不可以生成");
        }
        // 是否强制性
        if ($this->force) {
            return $this;
        }
        foreach ($this->fileList as $key => $val) {
            if (is_file($key)) {
                Log::write("文件已存在：{$key}");
                throw new FileException("文件已存在,可选择强制生成重新覆盖生成");
            }
        }
        return $this;
    }

    /**
     * 开始生成
     * @return array
     */
    public function create()
    {
        $this->check();
        foreach ($this->fileList as $key => $val) {

            // 判断文件夹是否存在,不存在就创建
            $fileArray = explode($this->DS, $key);
            array_pop($fileArray);
            $fileDir = implode($this->DS, $fileArray);
            if (!is_dir($fileDir)) {
                mkdir($fileDir, 0775, true);
            }

            // 写入
            file_put_contents($key, $val);
        }
        $data['fileList'] = array_keys($this->fileList);
        $data['viewFilename'] = str_replace($this->DS, '/', $this->viewFilename);
        $data['controllerUrl'] = $this->controllerUrl;
        return $data;
    }

    /**
     * 获取主表字段信息
     */
    public function getMainTableRow($table)
    {
        $dbname = $this->dbName;
        $prefix = $this->tablePrefix;
        $sqlone = "SELECT `table_name`,`table_comment` FROM information_schema.TABLES WHERE table_schema = ? AND table_name=?";
        $tableSchema = Db::query($sqlone, [$dbname, $table]);
        $table_comment = isset($tableSchema[0]['table_comment']) ? $tableSchema[0]['table_comment'] : str_replace($prefix, '', $table);

        //从数据库中获取表字段信息
        $sql = "SELECT * FROM `information_schema`.`columns` "
            . "WHERE TABLE_SCHEMA = ? AND table_name = ? "
            . "ORDER BY ORDINAL_POSITION";
        //加载主表的列
        $columnList = Db::query($sql, [$dbname, $table]);
        if (empty($columnList)) {
            throw new TableException("表不存在");
        }
        $fieldlist = [];
        foreach ($columnList as $key => $vo) {
            if ($vo['COLUMN_NAME'] == $this->deleteTime) {
                continue;
            }
            $column = [
                'field' => $vo['COLUMN_NAME'],
                'is_primary' => $vo['COLUMN_KEY'] == "PRI" ? true : false,
                'comment' => !empty($vo['COLUMN_COMMENT']) ? $vo['COLUMN_COMMENT'] : $vo['COLUMN_NAME'],
                'edit_show' => in_array($vo['COLUMN_NAME'], $this->ignoreFields) || $vo['COLUMN_KEY'] == "PRI" ? false : true,
                'edit_only_read' => false,
                'index_show' => true,
                'form_type' => $this->getFieldType($vo),
                'row_length' => 'medium',
                'is_query' => false,
                'query_type' => $this->getQueryType($this->getFieldType($vo)),
                'is_required' => $vo['IS_NULLABLE'] == "YES" ? false : true,
                'valid_type' => "",
                'default' => $vo['COLUMN_DEFAULT'],
                'numeric_precision' => $vo['NUMERIC_PRECISION'],
                'numeric_scale' => $vo['NUMERIC_SCALE'],
            ];
            $fieldlist[] = $this->buildColum($column);
        }
        $data['table_comment'] = $table_comment;
        $data['fieldlist'] = $fieldlist;
        return $data;
    }

    /**
     * 获取表
     */
    public function getSubTableRow($sub_table)
    {
        $dbname = $this->dbName;
        $prefix = $this->tablePrefix;

        $subsql = "SELECT * FROM `information_schema`.`columns` "
            . "WHERE TABLE_SCHEMA = ? AND table_name = ? "
            . "ORDER BY ORDINAL_POSITION";
        $subColumnList = Db::query($subsql, [$dbname, $sub_table]);
        if (empty($subColumnList)) {
            throw new TableException("从表不存在！");
        }
        $primary_key = "id";
        $foreign_key = str_replace($prefix, '', $sub_table) . "_id";
        $delete = false;
        $fieldlist = [];
        foreach ($subColumnList as $key => $vo) {
            if ($vo['COLUMN_NAME'] == $this->deleteTime) {
                continue;
            }
            $column = [
                'field' => $vo['COLUMN_NAME'],
                'comment' => !empty($vo['COLUMN_COMMENT']) ? $vo['COLUMN_COMMENT'] : $vo['COLUMN_NAME'],
                'form_type' => $this->getFieldType($vo),
                'is_query' => false,
                'query_type' => $this->getQueryType($this->getFieldType($vo)),
            ];
            $newColumn = $this->buildColum($column);

            if (in_array($newColumn['form_type'], ['select', 'selects', 'radio', 'checkbox', 'switch'])) {
                $newColumn['form_type'] = 'select';
            } else {
                if ($newColumn['form_type'] != 'date') {
                    $newColumn['form_type'] = "input";
                }
            }

            $fieldlist[] = $newColumn;
        }

        $data['delete'] = $delete;
        $data['primary_key'] = $primary_key;
        $data['foreign_key'] = $foreign_key;
        $data['fieldlist'] = $fieldlist;
        return $data;
    }

    /**
     * 开始删除
     * @return array
     */
    public function delete()
    {
        $deleteFile = [];
        foreach ($this->fileList as $key => $val) {
            if (is_file($key)) {
                unlink($key);
                $deleteFile[] = $key;
            }
        }
        return $deleteFile;
    }

    /**
     * 检测字段后缀
     * @param $string
     * @param $array
     * @return bool
     */
    protected function checkContain($string, $array)
    {
        foreach ($array as $vo) {

            if (substr($string, strlen($vo) * -1) === $vo) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取对应的模板信息
     * @param $name
     * @return false|string
     */
    protected function getTemplate($name)
    {
        return file_get_contents("{$this->dir}{$this->DS}templates{$this->DS}{$name}.code");
    }

    /**
     * 获取不带前缀的表名
     */
    protected function getTableNameExpPrefix($name)
    {
        return str_replace($this->tablePrefix, '', $name);
    }

    /**
     * 获取controller地址
     */
    protected function getControllerUrl($table)
    {
        $controllerFilename = "";
        $table = $this->getTableNameExpPrefix($table);
        $nodeArray = explode('_', $table);
        if (count($nodeArray) == 1) {
            $controllerFilename = $nodeArray[0];
        } else {
            foreach ($nodeArray as $k => $v) {
                if ($k == 0) {
                    $controllerFilename = "{$v}{$this->DS}";
                } else {
                    $controllerFilename .= "_" . lcfirst($v);
                }
            }
        }
        $nodeArray = explode($this->DS, $controllerFilename);
        $formatArray = [];
        foreach ($nodeArray as $vo) {
            $formatArray[] = $this->humpToLine(lcfirst($vo));
        }
        $controllerUrl = implode('.', $formatArray);
        return $controllerUrl;
    }

    public function lineToHump($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param $str
     * @return null|string|string[]
     */
    public function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }

    /**
     * 模板值替换
     * @param $string
     * @param $array
     * @return mixed
     */
    public function replaceTemplate($string, $array)
    {
        foreach ($array as $key => $val) {
            $string = str_replace("{{{" . $key . "}}}", $val, $string);
        }
        return $string;
    }
}

<?php


namespace models;

use classes\Cache;
use classes\Typiser;
use Exception;
use Throwable;
use TypeError;


abstract class Model
{
    /**
     * Свойство показывающее является ли объект новым и непривязанным к записи в БазеДанных
     */
    public bool $IsNew = true;


    private string          $Class             = "";
    private array           $LocalProperties   = [];
    private static ?string  $Table             = null;
    private static ?string  $PrimaryKey        = "Id";
    private static bool     $CreatedUpdatedAt  = false;


    /**
     * Метод вызывается при создании объекта
     */
    protected function OnCreate() {}

    /**
     * Метод вызывается при создании нового объекта
     */
    protected function OnCreateNew() {}

    /**
     * Метод вызывается при обновлений свойств объекта
     */
    protected function OnUpdate() {}

    /**
     * Метод вызывается при сохранении объекта в БазеДанных
     */
    protected function OnSave() {}

    /**
     * Метод вызывается при удалении записи из БазыДанных
     */
    protected function OnDelete() {}



    public function __construct(array $ValueProperties = null, bool $CheckAccess = true)
    {
        $this->Class = static::class;

        if(empty(Cache::Get('ClassProperties_' . $this->Class)))
        {
            $PropertiesTemp = $this->GetParentVars();
            $PropertiesTemp[] = "public ?int " . self::GetPrimaryKey() . " {public get; protected set;}";
            if(self::GetCreatedUpdatedAt())
            {
                $PropertiesTemp[] = "public int CreatedAt {public get; protected set;}";
                $PropertiesTemp[] = "public int UpdatedAt {public get; protected set;}";
            }

            $Properties = [];
            foreach(array_reverse($PropertiesTemp) as $Property)
                $Properties = array_merge($Properties, $this->ParseProperty($Property));

            Cache::Set('ClassProperties_' . $this->Class, ($this->LocalProperties = $Properties));
        }
        else
            $this->LocalProperties = Cache::Get('ClassProperties_' . $this->Class);

        if($CheckAccess)
            Authorization::IsAccess(null, "Get" . self::GetClassName());

        if(!empty($ValueProperties))
        {
            $this->IsNew = false;

            foreach($ValueProperties as $key => $obj)
                if(!empty($this->LocalProperties[$key]) && $this->TypeCheckValue($this->LocalProperties[$key]["Type"], $obj))
                    $this->LocalProperties[$key]["Value"] = $obj;

            if($CheckAccess && !empty($this->LocalProperties[self::GetPrimaryKey()]["Value"]) && method_exists(Authorization::class, "IsAccess" . self::GetClassName()))
                call_user_func(Authorization::class . "::IsAccess" . self::GetClassName(), Authentication::GetAuthUser(), (int)$this->LocalProperties[self::GetPrimaryKey()]["Value"]);
        }
        $this->HashingProperties();

        $this->OnCreate();

        if($this->IsNew)
        {
            if(self::GetCreatedUpdatedAt())
            {
                $this->LocalProperties['UpdatedAt']["Value"] = time();
                $this->LocalProperties['CreatedAt']["Value"] = time();
            }

            $this->OnCreateNew();
        }
    }


    /**
     * Выполняет обновление объекта по записи в БазеДанных
     */
    public function Update()
    {
        $Obj = QueryCreator::FindOne(self::GetTable(), self::GetPrimaryKey() . " = $1", [$this->{self::GetPrimaryKey()}])->Run()[0];
        foreach($Obj as $key => $val)
        {
            if(!empty($this->LocalProperties[$key]))
            {
                $this->LocalProperties[$key]["Value"]       = $val;
                $this->LocalProperties[$key]["IsUpdate"]    = false;
            }
        }
        $this->HashingProperties();

        $this->OnUpdate();
    }


    /**
     * Выполняет сохранение записи в БазеДанных
     */
    public function Save(bool $CheckAccess = true)
    {
        if(!$CheckAccess)
            Authorization::IsAccess(Authentication::GetAuthUser(), "Set" . self::GetClassName());

        if(self::GetCreatedUpdatedAt())
            $this->LocalProperties['UpdatedAt']["Value"] = time();

        if(empty($this->__get(self::GetPrimaryKey())))
        {
            $PropertiesWrite = array_filter($this->LocalProperties, function($obj, $key) { return $key != self::GetPrimaryKey() ? $obj["Write"] : false; }, ARRAY_FILTER_USE_BOTH);
            $Val = [];
            foreach($PropertiesWrite as &$obj)
            {
                $Val[] = "$" . (count($Val) + 1);
                $obj = $this->PrepareValue($obj);
            }
            $this->LocalProperties[self::GetPrimaryKey()]["Value"] = (int)((QueryCreator::Create(self::GetTable(), array_keys($PropertiesWrite), implode(",", $Val), array_column($PropertiesWrite, "Value"), self::GetPrimaryKey()))->Run()[0]->{self::GetPrimaryKey()});
        }
        else
        {
            $this->CheckIsUpdateByHash();

            $PropertiesWrite = array_filter($this->LocalProperties, function($obj, $key) { return $key != self::GetPrimaryKey() ? ($obj["Write"] && $obj["IsUpdate"]) : false; }, ARRAY_FILTER_USE_BOTH);
            if(empty($PropertiesWrite))
                return;

            $Val = [];
            foreach($PropertiesWrite as $key => &$obj)
            {
                $Val[] = "\"$key\" = $" . (count($Val) + 1);
                $obj = $this->PrepareValue($obj);
            }

            $Parameters = array_column($PropertiesWrite, "Value");
            $Parameters[] = $this->LocalProperties[self::GetPrimaryKey()]["Value"];
            (QueryCreator::Update(self::GetTable(), implode(",", $Val), '"' . self::GetPrimaryKey() . '" = $' . (count($Val) + 1), $Parameters))->Run();
        }

        foreach($this->LocalProperties as &$Property)
            $Property['IsUpdate'] = false;

        $this->OnSave();
    }


    /**
     * Выполняет удаление записи из БазыДанных
     */
    public function Delete(bool $CheckAccess = true)
    {
        if(!$CheckAccess)
            Authorization::IsAccess(Authentication::GetAuthUser(), "Set" . self::GetClassName());

        $this->OnDelete();
        QueryCreator::Delete(self::GetTable(), '"' . self::GetPrimaryKey() . '"' . " = $1", [$this->{self::GetPrimaryKey()}])->Run();
    }


    /**
     * Выполняет отвязку объекта от записи в БазеДанных
     */
    public function Untie()
    {
        $this->LocalProperties[self::GetPrimaryKey()]["Value"] = null;
        $this->IsNew = true;
    }


    /**
     * Возвращает массив из публичных свойств объекта
     */
    public function ToArray(array $Keys = null) : array
    {
        $Out = [];
        foreach($this->LocalProperties as $key => $obj)
            if($obj["Get"] == "public" && ($Keys == null || in_array($key, $Keys)))
                $Out[$key] = $this->__get($key);
        return $Out;
    }


    /**
     * Возвращает фактическое имя класса. Без имён пространства
     */
    static public function GetClassName() : string
    {
        return end(explode("\\", static::class));
    }


    /**
     * Возвращает имя класса
     */
    static public function GetType() : string
    {
        return static::class;
    }





    private function ParseProperty(string $Property) : array
    {
        preg_match("/\{.+\}/", $Property, $Params);
        preg_match("/.+::/", $Property, $ParentClass);
        preg_match("/\s*=\s{0,1}(.+)/", $Property, $DefaultValue);
        $Property       = trim(preg_replace("/\{.+\}/", "", $Property));
        $Property       = trim(preg_replace("/$DefaultValue[0]/", "", $Property));
        $Property       = trim(preg_replace("/.+::/", "", $Property));
        $Property       = preg_split("/\s+/", $Property);
        $Params         = preg_split("/;\s{0,}/", trim(preg_replace("/[{}]/", "", $Params[0])));
        $ParentClass    = empty($ParentClass) ? null : trim(str_replace("::", "", $ParentClass[0]));

        $this->ModifierCheck($Property[0]);

        $Type = $this->ConvertTypeName($Property[1]);
        $this->TypeCheck($Type);
        $OutProperty[$Property[2]] = [
            "Type"          => $Type,
            "Get"           => $Property[0],
            "Set"           => $Property[0],
            "IsUpdate"      => false,
            "Write"         => true,
            "Value"         => $this->GetDefaultValueForType($Type),
            "ParentClass"   => $ParentClass,
            "HashValue"     => null
        ];

        if(isset($DefaultValue[1]))
        {
            $Value = Typiser::TypeConversion($DefaultValue[1]);
            $this->TypeCheckValue($Type, $Value);
            $OutProperty[$Property[2]]['Value'] = $Value;
        }

        foreach($Params as $Param)
        {
            if(!empty($Param))
            {
                $Param = preg_split("/\s+/", $Param);

                switch(mb_strtolower($Param[1]))
                {
                    case 'get':
                        $this->ModifierCheck($Param[0], $OutProperty[$Property[2]]["Get"]);
                        $OutProperty[$Property[2]]["Get"] = $Param[0];
                        break;

                    case 'set':
                        $this->ModifierCheck($Param[0], $OutProperty[$Property[2]]["Set"]);
                        $OutProperty[$Property[2]]["Set"] = $Param[0];
                        break;

                    default:
                        switch(mb_strtolower($Param[0]))
                        {
                            case 'write':
                                $OutProperty[$Property[2]]["Write"] = $Param[1] == "true";
                                break;

                            default:
                                throw new Exception("Invalid property");
                            break;
                        }
                    break;
                }
            }
        }

        return $OutProperty;
    }


    private function GetDefaultValueForType(string $Type)
    {
        if($Type[0] == "?")
            return null;

        switch($Type)
        {
            case "integer":
            case "float":
                return 0;
                break;

            case "boolean":
                return false;
                break;

            case "array":
                return [];
                break;

            case "string":
                return "";
                break;
        }
    }


    private function ConvertTypeName($Type) : string
    {
        if($this->IsDefaultType($Type))
            $Type = mb_strtolower($Type);

        $IsNull = false;
        if($Type[0] == "?")
        {
            $Type = ltrim($Type, "?");
            $IsNull = true;
        }

        switch($Type)
        {
            case 'int':
                $Type = "integer";
                break;

            case 'bool':
                $Type = "boolean";
                break;
        }

        return $IsNull ? "?" . $Type : $Type;
    }


    private function GetParentVars() : array
    {
        $Classes = [$this->Class];
        while(true)
        {
            $Class = get_parent_class($Classes[array_key_last($Classes)]);
            if($Class !== false)
                $Classes[] = $Class;
            else
                break;
        }
        
        $Out = get_class_vars($this->Class)["Properties"];
        //Фильтр убирает приветные свойства наследуемые от родителей
        foreach(array_reverse($Classes) as $Class)
        {
            if($Class != $this->Class)
            {
                $Vars = get_class_vars($Class)["Properties"];
                foreach($Vars as $key => $obj)
                {
                    $Vars[$key] = "$Class::" . $Vars[$key];
                    if(explode(" ", $obj)[0] == "private")
                        unset($Vars[$key]);
                }

                $Out = array_merge($Out, empty($Vars) ? [] : $Vars);
            }
        }
        
        return $Out;
    }


    private function PrepareValue($Value)
    {
        if(is_object($Value))
        {
            if(method_exists($Value, "ToArray") && $Value instanceof Model)
                $Value = array_merge($Value->ToArray(), ['__model::serializeClassName' => get_class($Value)]);
            else
                $Value = ['__model::serialize' => base64_encode(serialize($Value))];
        }
        else if(is_array($Value))
            foreach($Value as &$obj)
                $obj = $this->PrepareValue($obj);

        return $Value;
    }


    private function ModifierCheck(string $Modifier, string $OldModifier = null) : bool
    {
        $ModifierLevels = [
            "private"   => 0,
            "public"    => 2,
            "protected" => 1
        ];

        $Modifier = mb_strtolower($Modifier);
        switch($Modifier)
        {
            case "private":
            case "public":
            case "protected":
                if(!empty($OldModifier))
                    if($ModifierLevels[$OldModifier] < $ModifierLevels[$Modifier])
                        throw new Exception("Invalid modifier level");

                return true;
                break;

            default:
                throw new Exception("Invalid modifier");
            break;
        }
    }


    private function IsDefaultType(string $Type) : bool
    {
        $Type = mb_strtolower($Type);

        if($Type[0] == "?")
            $Type = ltrim($Type, "?");

        return in_array($Type, [
            'string',
            'mixed',
            'array',
            'integer',
            'boolean',
            'double',
            'object'
        ]);
    }


    private function TypeCheck(string $Type) : bool
    {
        if($Type[0] == "?")
            $Type = ltrim($Type, "?");

        if($this->IsDefaultType($Type))
            return true;
            
        if(class_exists($Type) || interface_exists($Type))
            return true;

        throw new Exception('Invalid type');
    }


    private function AccessCheck(string $Modifier) : bool
    {
        switch(mb_strtolower($Modifier))
        {
            case "protected":
            case "private":
                $BackTrace = debug_backtrace();
                if(strrpos(str_replace('\\', '/', $BackTrace[1]["file"]), str_replace('\\', '/', $this->Class) . ".php") === false && strrpos(str_replace('\\', '/', $BackTrace[1]["file"]), str_replace('\\', '/', self::class) . ".php") === false)
                    throw new Exception("Cannot access private property");
                break;

            case "public":
                return true;
                break;

            default:
                throw new Exception("Invalid modifier");
            break;
        }

        return true;
    }


    private function HashingProperties()
    {
        foreach($this->LocalProperties as &$Property)
            $Property['HashValue'] = self::HashingValue($Property['Value']);
    }


    private function CheckIsUpdateByHash()
    {
        foreach($this->LocalProperties as &$Property)
            if(self::HashingValue($Property['Value']) != $Property['HashValue'])
                $Property['IsUpdate'] = true;

        $this->HashingProperties();
    }


    static private function HashingValue($Value) : string
    {
        return md5(serialize($Value));
    }


    private function TypeCheckValue(string $Type, &$Value) : bool
    {
        if(!isset($Value))
        {
            if($Type[0] == "?")
                return true;
            throw new TypeError("Value cannot be null");
        }

        if($Type[0] == "?")
            $Type = ltrim($Type, "?");

        $Value = $this->TryFixData($Type, $Value);
        if($Type != gettype($Value) && $Type != "mixed" && !($Value instanceof $Type))
            throw new TypeError("Does not match the $Type type");

        return true;
    }


    private function TryFixData(string $Type, $Value)
    {
        if($Type[0] == "?")
            $Type = ltrim($Type, "?");

        if($Type == "array" && !is_array($Value))
        {
            $Temp = json_decode($Value, true);
            if(isset($Temp))
                $Value = $this->TryObjectsDecodeInArray($Temp);
        }

        if($Type == "double" && !is_double($Value))
        {
            $Temp = (double)$Value;
            if(isset($Temp))
                $Value = $Temp;
        }
      
        if($Type == "string" && !is_string($Value))
        {
            $Temp = (string)$Value;
            if(isset($Temp))
                $Value = $Temp;
        }
        
        if(preg_match("/\b__model::serializeClassName\b/", $Value) || preg_match("/\b__model::serialize\b/", $Value) && !$this->IsDefaultType($Type))
        {
            $Temp = json_decode($Value, true);
            if(isset($Temp) && ($Temp = $this->TryObjectDecode($Temp)) != null)
                $Value = $Temp;
        }

        return $Value;
    }


    private function TryObjectDecode(array $Array) : ?object
    {
        $Obj = null;
        if(!empty($Array['__model::serializeClassName']))
            $Obj = new $Array['__model::serializeClassName']($Array);

        if(!empty($Array['__model::serialize']))
            $Obj = unserialize(base64_decode($Array['__model::serialize']));

        return is_object($Obj) ? $Obj : null;
    }


    private function TryObjectsDecodeInArray(array $Array) : array
    {
        foreach($Array as &$obj)
        {
            if(!is_array($obj))
                continue;

            $Temp = null;
            if(!empty($obj) && ($Temp = $this->TryObjectDecode($obj)))
                $obj = $Temp;
            else
                $obj = $this->TryObjectsDecodeInArray($obj);
        }

        return $Array;
    }


    static private function GetTable() : string
    {
        if(empty(static::$Table))
            throw new Exception("Table Name is empty");

        return static::$Table;
    }


    static private function GetCreatedUpdatedAt() : bool
    {
        return (bool)static::$CreatedUpdatedAt;
    }


    static private function GetPrimaryKey() : string
    {
        if(empty(static::$PrimaryKey))
            return self::$PrimaryKey;

        return static::$PrimaryKey;
    }


    public function &__get($Name)
    {
        if(empty($this->LocalProperties[$Name]))
            throw new Exception("Invalid property");
        $this->AccessCheck($this->LocalProperties[$Name]["Get"]);

        $Type = $this->LocalProperties[$Name]["Type"];
        if($Type[0] == "?")
            $Type = ltrim($Type, "?");

        if($Type == 'array')
            $Value = &$this->LocalProperties[$Name]["Value"];
        else
            $Value = $this->LocalProperties[$Name]["Value"];

        $Method = "__get$Name";
        if(method_exists($this, $Method))
            $Value = $this->$Method($Value);

        return $Value;
    }


    public function __set($Name, $Value)
    {
        if(empty($this->LocalProperties[$Name]))
            throw new Exception("Invalid property");
        $this->AccessCheck($this->LocalProperties[$Name]["Set"]);

        $this->TypeCheckValue($this->LocalProperties[$Name]["Type"], $Value);

        $Method = "__set$Name";
        if(method_exists($this, $Method))
            $Value = $this->$Method($Value);
            
        $this->LocalProperties[$Name]["Value"] = $Value;
        $this->LocalProperties[$Name]["IsUpdate"] = true;
    }

    public function __isset($Name)
    {
        return isset($this->LocalProperties[$Name]["Value"]);
    }

    public function __unset($Name)
    {
        unset($this->LocalProperties[$Name]["Value"]);
    }




    /**
     * Finds Methods
     */
    /**
     * Выполняет поиск записей в БазеДанных и возвращает объекты
     */
    static public function FindAll(string $Where = null, array $Parameters = [], int $Offset = null, int $Limit = null, bool $CheckAccess = true) : array
    {
        $Class = static::class;
        $Out = [];
        foreach(QueryCreator::Find(self::GetTable(), "*", $Where, $Parameters, $Offset, $Limit)->Run() as $obj)
        {
            try
            {
                $Out[] = new $Class((array)$obj, $CheckAccess);
            }
            catch(Throwable $error) {}
        }
        return $Out;
    }


    /**
     * Выполняет поиск записи в БазеДанных и возвращает объект
     */
    static public function FindOne(string $Where = null, array $Parameters = [], bool $CheckAccess = true) : ?Model
    {
        return self::FindAll($Where, $Parameters, null, 1, $CheckAccess)[0];
    }


    /**
     * Выполняет поиск записи по id и возвращает объект
     */
    static public function FindById(int $Id, bool $CheckAccess = true) : ?Model
    {
        return self::FindOne('"' . self::GetPrimaryKey() . '" = $1', [$Id], $CheckAccess);
    }
}
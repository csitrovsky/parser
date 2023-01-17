<?php


namespace app\src\spout;

class ExcelColumn
{
    
    /**
     * Название колонки в шапке
     *
     * @var string $title
     */
    public $title = '';
    
    /**
     * Поле из массива данных
     *
     * @var string $field
     */
    public $field = '';
    
    /**
     * Тип данных колонки
     *
     * @var int|null
     */
    public $type = null;
    
    /**
     * Значение по умолчанию
     *
     * @var string
     */
    public $default = '';
    
    /**
     * ExcelColumn constructor.
     *
     * @param string $title
     * @param string $field
     * @param null   $type
     * @param string $default
     */
    public function __construct(string $title = '', string $field = '', $type = null, string $default = '')
    {
        
        $this->title = $title;
        $this->field = $field;
        $this->type = $type;
        $this->default = $default;
    }
}

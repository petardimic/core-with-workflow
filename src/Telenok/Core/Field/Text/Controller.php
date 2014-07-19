<?php

namespace Telenok\Core\Field\Text;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Controller extends \Telenok\Core\Interfaces\Field\Controller {

	protected $key = 'text';
	protected $ruleList = ['text_width' => ['integer', 'between:20,2000'], 'text_height' => ['integer', 'between:20,2000']];
	protected $specialField = ['text_width', 'text_height', 'text_default'];

    public function getModelAttribute($model, $key, $value, $field)
    {
        if ($field->multilanguage)
        {
            $value = \Illuminate\Support\Collection::make(json_decode($value ?: '[]', true));
        }

        return $value;
    }

    public function setModelAttribute($model, $key, $value, $field)
    { 
        if ($field->multilanguage)
        { 
			$default = json_decode($field->text_default ?: "[]", true);

            foreach ($default as $language => $v)
            {
                if (!isset($value[$language]))
                {
                    $value[$language] = $v;
                }
            }
            
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        else if (!strlen($value))
        {
            $value = $field->text_default ?: "";
        }

        $model->setAttribute($key, $value);
    }

    public function getModelSpecialAttribute($model, $key, $value)
    {
        try
        {
			if (in_array($key, ['text_default']) && $model->multilanguage)
			{ 
				return \Illuminate\Support\Collection::make(json_decode($value, true));
			}
			else
			{
				return parent::getModelSpecialAttribute($model, $key, $value);
			}
        }
        catch (\Exception $e)
        {
            return null;
        }
    }
    
    public function setModelSpecialAttribute($model, $key, $value)
    {  
		if (in_array($key, ['text_default']) && $model->multilanguage)
		{
			$default = [];

			if ($value instanceof \Illuminate\Support\Collection) 
			{
				if ($value->count())
				{
					$value = $value->toArray();
				}
				else
				{
					$value = $default;
				}
			}
			else
			{
				$value = $value ? : $default;
			} 

			$model->setAttribute($key, json_encode($value, JSON_UNESCAPED_UNICODE));
		}
		else
		{
			parent::setModelSpecialAttribute($model, $key, $value);
		}

        return true;
    }
	
    public function postProcess($model, $type, $input)
    {
		$table = $model->fieldObjectType()->first()->code;
        $fieldName = $model->code;

		if (!\Schema::hasColumn($table, $fieldName) && !\Schema::hasColumn($table, "`{$fieldName}`"))
		{
			\Schema::table($table, function(Blueprint $table) use ($fieldName)
			{
				$table->text($fieldName)->nullable();
			});
		}

        $fields = []; 
        
        if ($input->get('required'))
        {
            $fields['rule'][] = 'required';
        }
 
        
        $model->fill($fields)->save();

        return parent::postProcess($model, $type, $input);
    }	
}

?>
<?php

namespace Telenok\Core\Field\Time;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration; 

class Controller extends \Telenok\Core\Interfaces\Field\Controller {

    protected $key = 'time'; 
    protected $allowMultilanguage = false;
	protected $specialField = ['time_default'];

    public function getDateField($model, $field)
    { 
		return [$field->code];
    } 
    
    public function getDateSpecialField($model)
    { 
		return ['time_default'];
    }

    public function setModelAttribute($model, $key, $value, $field)
    {   
        if ($value === null)
        {
            $value = $field->time_default ?: null;
        }
        else if (is_string($value))
        {
            $value = \Carbon\Carbon::createFromFormat('H:i:s', $value);
        }

        return parent::setModelAttribute($model, $key, $value, $field);
    }
    
    public function getModelSpecialAttribute($model, $key, $value)
    {
        try
        {
			if ($key == 'time_default' && $value === null)
			{ 
                return \Carbon\Carbon::now();
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
        if ($key == 'time_default')
		{
            if ($value === null)
            {
                $value = \Carbon\Carbon::now();
            }
            else if (is_scalar($value) && $value)
            {
                $value = \Carbon\Carbon::createFromFormat('H:i:s', $value);
            }
		}
			
        parent::setModelSpecialAttribute($model, $key, $value);

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
				$table->timestamp($fieldName)->nullable();
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
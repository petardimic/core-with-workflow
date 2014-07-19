<?php

namespace Telenok\Core\Field\MorphManyToMany;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Controller extends \Telenok\Core\Interfaces\Field\Controller {

    protected $key = 'morph-many-to-many';
    protected $specialField = ['morph_many_to_many_has', 'morph_many_to_many_belong_to'];
    protected $allowMultilanguage = false;

    public function getModelField($model, $field)
    {
		return [];
    } 

	public function getLinkedModelType($field)
	{
		return \Telenok\Object\Type::whereIn('id', [$field->morph_many_to_many_has, $field->morph_many_to_many_belong_to])->first();
	}

    public function getTitleList($id = null) 
    {
        $term = trim(\Input::get('term'));
        $return = [];
        
        try 
        {
            $class = \Telenok\Object\Sequence::getModel($id)->class_model;
            
            $class::where(function($query) use ($term)
			{
				\Illuminate\Support\Collection::make(explode(' ', $term))
						->reject(function($i) { return !trim($i); })
						->each(function($i) use ($query)
				{
					$query->where('title', 'like', "%{$i}%");
				});
			})
			->take(20)->get()->each(function($item) use (&$return)
            {
                $return[] = ['value' => $item->id, 'text' => $item->translate('title')];
            });
        }
        catch (\Exception $e) {}

        return $return;
    }

    public function getListButtonExtended($item, $field, $type, $uniqueId)
    {
        return '<div class="hidden-phone visible-lg btn-group">
                    <button class="btn btn-minier btn-info" title="'.$this->LL('list.btn.edit').'" 
                        onclick="editM2M'.$uniqueId.'(this, \''.\URL::route('cmf.module.objects-lists.wizard.edit', ['id' => $item->getKey() ]).'\'); return false;">
                        <i class="fa fa-pencil"></i>
                    </button>
                    
                    <button class="btn btn-minier btn-light" onclick="return false;" title="' . $this->LL('list.btn.' . ($item->active ? 'active' : 'inactive')) . '">
                        <i class="fa fa-check ' . ($item->active ? 'green' : 'white'). '"></i>
                    </button>
                    ' .
                    ($field->allow_delete ? '
                    <button class="btn btn-minier btn-danger trash-it" title="'.$this->LL('list.btn.delete').'" 
                        onclick="deleteM2M'.$uniqueId.'(this); return false;">
                        <i class="fa fa-trash-o"></i>
                    </button>' : ''
                    ). '
                </div>';
    } 

    public function getFilterQuery($field = null, $model = null, $query = null, $name = null, $value = null) 
    {
		if (!empty($value))
		{
			if ($field->morph_many_to_many_has)
			{
				$typeHasMany = \Telenok\Object\Type::findOrFail($field->morph_many_to_many_has);
				$pivotTable = 'pivot_morph_m2m_' . $field->code . '_' . $typeHasMany->code;

				$query->join($pivotTable, function($join) use ($pivotTable, $field, $model)
				{
					$join->on($pivotTable . '.' . $field->code . '_linked_id', '=', $model->getTable() . '.id');
				});

				$query->join($typeHasMany->code, function($join) use ($pivotTable, $typeHasMany, $field)
				{
					$join->on($pivotTable . '.morph_id', '=', $typeHasMany->code . '.id');
				});

				$query->whereIn($typeHasMany->code.'.id', (array)$value);
				$query->where($pivotTable . '.' . $field->code . '_type', get_class(\App::build($typeHasMany->class_model)));
			}
			else
			{
				$typeHasMany = \Telenok\Object\Type::findOrFail($field->morph_many_to_many_belong_to);
				$typeRelated = $field->fieldObjectType()->first();

				$morphToField = preg_replace('/_'.$typeHasMany->code.'$/', '_' . $typeRelated->code, $field->code);
				$morphManyField = preg_replace('/_'.$typeHasMany->code.'$/', '', $field->code);

				$pivotTable = 'pivot_morph_m2m_' . $morphToField;

				$query->join($pivotTable, function($join) use ($pivotTable, $model)
				{
					$join->on($pivotTable . '.morph_id', '=', $model->getTable() . '.id');
				});

				$query->join($typeHasMany->code, function($join) use ($pivotTable, $typeHasMany, $morphManyField)
				{
					$join->on($pivotTable . '.' . $morphManyField . '_linked_id', '=', $typeHasMany->code . '.id');
				});

				$query->whereIn($typeHasMany->code.'.id', (array)$value);
				$query->where($pivotTable . '.' . $morphManyField . '_type', get_class($model));
			}
		}
    }

    public function getFilterContent($field = null)
    {
        $uniqueId = uniqid();
        $option = [];
        
        $id = $field->morph_many_to_many_has ?: $field->morph_many_to_many_belong_to;
        
        $class = \Telenok\Object\Sequence::getModel($id)->class_model;
        
        $class::take(200)->get()->each(function($item) use (&$option)
        {
            $option[] = "<option value='{$item->id}'>[{$item->id}] {$item->translate('title')}</option>";
        });
        
        $option[] = "<option value='0' disabled='disabled'>...</option>";
        
        return '
            <select class="chosen" multiple data-placeholder="'.$this->LL('notice.choose').'" id="input'.$uniqueId.'" name="filter['.$field->code.'][]">
            ' . implode('', $option) . ' 
            </select>
            <script>
                jQuery("#input'.$uniqueId.'").ajaxChosen({ 
                    keepTypingMsg: "'.$this->LL('notice.typing').'",
                    lookingForMsg: "'.$this->LL('notice.looking-for').'",
                    type: "GET",
                    url: "'.\URL::route($this->getRouteListTitle(), ['id' => (int)$id]).'", 
                    dataType: "json",
                    minTermLength: 1
                }, 
                function (data) 
                {
                    var results = [];

                    jQuery.each(data, function (i, val) {
                        results.push({ value: val.value, text: val.text });
                    });

                    return results;
                },
                {
                    width: "200px",
                    no_results_text: "'.$this->LL('notice.not-found').'" 
                    
                });
            </script>';
    }
 
    public function getListFieldContent($field, $item, $type = null)
    {
        $method = camel_case($field->code);

        $items = [];
        $rows = \Illuminate\Support\Collection::make($item->$method()->take(8)->getResults());
        
        if ($rows->count())
        {
            foreach($rows->slice(0, 7, TRUE) as $row)
            {
                $items[] = $row->translate('title');
            }

            return '"'.implode('", "', $items).'"'.(count($rows)>7 ? ', ...' : '');
        }
    }

    public function saveModelField($field, $model, $input)
    {
        $idsAdd = array_unique((array)$input->get("{$field->code}_add", []));
        $idsDelete = array_unique((array)$input->get("{$field->code}_delete", []));
         
        if ( (!empty($idsAdd) || !empty($idsDelete)))
        { 
            $method = camel_case($field->code);
             
            if (in_array('*', $idsDelete))
            {
                $model->$method()->detach();
            }
            else if (!empty($idsDelete))
            {
                $model->$method()->detach($idsDelete);
            }

            foreach($idsAdd as $id)
            {
                try
                {
                    $model->$method()->attach($id);
                }
                catch(\Exception $e) {}
            }

        }

        return $model;
    }

    public function preProcess($model, $type, $input)
    {
		$input->put('morph_many_to_many_has', intval(\Telenok\Object\Type::where('code', $input->get('morph_many_to_many_has'))->orWhere('id', $input->get('morph_many_to_many_has'))->pluck('id')));
		$input->put('multilanguage', 0);
		$input->put('allow_sort', 0); 
		
        return parent::preProcess($model, $type, $input);
    } 
	
    public function postProcess($model, $type, $input)
    { 
        try
        {
			$model->fill(['morph_many_to_many_has' => $input->get('morph_many_to_many_has')])->save();
			
			if (!$input->get('morph_many_to_many_has'))
			{
				return parent::postProcess($model, $type, $input);
			} 

            $typeMorphMany = $model->fieldObjectType()->first();
            $typeBelongTo = \Telenok\Object\Type::findOrFail($input->get('morph_many_to_many_has')); 

            $morphManyCode = $model->code;
            $morphToCode = $morphManyCode . '_' . $typeMorphMany->code;

            $classModelMorphMany = $typeMorphMany->class_model;
            $classModelMorphTo = $typeBelongTo->class_model;

            $tableMorphTo = $typeBelongTo->code;

            $morphManyObject = \App::build($classModelMorphMany);
            $morphToObject = \App::build($classModelMorphTo);

            $pivotTable = 'pivot_morph_m2m_' . $morphManyCode . '_' . $tableMorphTo;

            $morphMany = [
                'method' => camel_case($morphManyCode),
                'name' => $morphManyCode,
                'class' => $classModelMorphTo,
                'table' => $pivotTable,
                'foreignKey' => $morphManyCode . '_linked_id',
                'otherKey' => 'morph_id',
            ];

            $morphTo = [
                'method' => camel_case($morphToCode),
                'name' => $morphManyCode,
                'class' => $classModelMorphMany,
                'table' => $pivotTable,
                'foreignKey' => 'morph_id',
                'otherKey' => $morphManyCode . '_linked_id',
            ];

            if (!\Schema::hasTable($pivotTable)) 
			{
                \Schema::create($pivotTable, function(Blueprint $table) use ($tableMorphTo, $morphManyCode)
                {
                    $table->increments('id');
                    $table->timestamps();
                    $table->integer('morph_id')->unsigned()->nullable();
                    $table->integer($morphManyCode . '_linked_id')->unsigned()->nullable();
                    $table->string($morphManyCode . '_type')->nullable();

                    $table->unique(['morph_id', $morphManyCode . '_linked_id', $morphManyCode . '_type'], 'uniq_key');

					$table->foreign($morphManyCode . '_linked_id')->references('id')->on('object_sequence')->onDelete('cascade');
                });
            }

            if ($input->get('create_belong') !== false) 
            {
				$title = $input->get('title_belong', []);
				$title_list = $input->get('title_list_belong', []);

				foreach($typeMorphMany->title->toArray() as $language => $val)
				{
					$title[$language] = array_get($title, $language, $val . '/' . $model->translate('title', $language));
				}

				foreach($typeMorphMany->title_list->toArray() as $language => $val)
				{
					$title_list[$language] = array_get($title_list, $language, $val . '/' . $model->translate('title_list', $language));
				}
  
				$tabTo = $this->getFieldTabBelongTo($typeBelongTo->getKey(), $input->get('field_object_tab'));
  
				$toSave = [
					'title' => $title,
					'title_list' => $title_list,
					'key' => $this->getKey(),
					'code' => $morphToCode,
					'field_object_type' => $typeBelongTo->getKey(),
					'field_object_tab' => $tabTo->getKey(),
					'morph_many_to_many_belong_to' => $typeMorphMany->getKey(),
					'show_in_list' => $input->get('show_in_list_belong', $model->show_in_list),
					'show_in_form' => $input->get('show_in_form_belong', $model->show_in_form),
					'allow_search' => $input->get('allow_search_belong', $model->allow_search),
					'allow_delete' => $input->get('allow_delete_belong', $model->allow_delete),
					'multilanguage' => 0,
					'active' => $input->get('active_belong', $model->active),
					'allow_create' => $input->get('allow_create_belong', $model->allow_create),
					'allow_choose' => $input->get('allow_choose_belong', $model->allow_choose),
					'allow_update' => $input->get('allow_update_belong', $model->allow_update),
					'field_order' => $input->get('field_order_belong', $model->field_order),
				];

				$validator = $this->validator(new \Telenok\Object\Field(), $toSave, []);

				if ($validator->passes()) 
				{
					\Telenok\Object\Field::create($toSave);
				}

				if (!$this->validateMethodExists($morphToObject, $morphTo['method']))
				{
					$this->updateModelFile($morphToObject, $morphTo, 'morphTo', __DIR__);
				} 
				else
				{
					\Session::flash('warning.morphTo', $this->LL('error.method.defined', ['method' => $morphTo['method'], 'class' => $classModelMorphTo]));
				} 

			}

            if (!$this->validateMethodExists($morphManyObject, $morphMany['method']))
            {
                $this->updateModelFile($morphManyObject, $morphMany, 'morphMany', __DIR__);
            } 
            else
            {
                \Session::flash('warning.morphOne', $this->LL('error.method.defined', ['method' => $morphMany['method'], 'class' => $classModelMorphMany]));
            }
        }
        catch (\Exception $e) 
        {
            throw $e;
        }

        return parent::postProcess($model, $type, $input);
    } 

}

?>
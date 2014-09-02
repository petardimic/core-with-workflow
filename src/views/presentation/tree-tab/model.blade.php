@extends('core::layout.model')


@section('script')
	@parent
	
	@section('buttonType')
 
        if (button_type=='close')
        {	
			var divId = $el.closest('div.tab-pane').attr('id');

			jQuery('li a[href=#' + divId + '] i.fa.fa-times').click();
			
			return;
        }
		else if (button_type == 'delete.close')
		{ 
			if (confirm('{{{ $controller->LL('notice.sure') }}}'))
			{
				$el.attr('action', "{{$controller->getRouterDelete(['id' => $model->getKey()])}}");
			}
			else
			{
				return;
			}
		}
	@stop

@stop


@section('form')

	@parent
    
	@section('formBtn')
	
    <div class='form-actions center no-margin'>
        <button type="submit" class="btn btn-success" onclick="jQuery(this).closest('form').data('btn-clicked', 'save');">
            <i class="fa fa-floppy-o"></i>
            {{$controller->LL('btn.save')}}
        </button>
        <button type="submit" class="btn btn-info" onclick="jQuery(this).closest('form').data('btn-clicked', 'save.close');">
            <i class="fa fa-floppy-o"></i>
            {{$controller->LL('btn.save.close')}}
        </button>
        <button type="submit" class="btn" onclick="jQuery(this).closest('form').data('btn-clicked', 'close');">
            <i class="fa fa-floppy-o"></i>
            {{$controller->LL('btn.close')}}
        </button>
    </div>

	@overwrite
     
@stop
 
 
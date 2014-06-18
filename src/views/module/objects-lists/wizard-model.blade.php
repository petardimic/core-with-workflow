@extends('core::layout.model')


<script>

if (!telenok.hasPresentation('{{$presentation}}'))
{
    telenok.addPresentation('{{$presentation}}', function(param)
    {
        function presentationModelWizard()
        {
            var presentationDomId = '';
            var moduleKey = '';
            var presentationParam = {};
            var _this = this;

            this.getPresentationDomId = function()
            {
                return _this.presentationDomId;
            }
 
            this.setParam = function(param)
            {
                return _this;
                _this.presentationParam = param;
                _this.presentationDomId = telenok.getPresentationDomId(param.presentation);
                _this.moduleKey = param.key;
                return _this;
            }
 
            this.addDataTable = function(param)
            {
                param = jQuery.extend({}, {
                    "multipleSelection": true,
                    "aoColumns": [],
                    "autoWidth": true,
                    "bProcessing": true,
                    "bServerSide": param.sAjaxSource ? true : false,
                    "bDeferRender": '',
                    "bJQueryUI": false,
                    "iDisplayLength": 10,
                    "sDom": "<'row'<'col-md-6'T><'col-md-6'f>r>t<'row'<'col-md-6'T><'col-md-6'p>>",
                    "oLanguage": {
                        "oPaginate": {
                            "sNext": "{{{ \Lang::get('core::default.btn.next') }}}",
                            "sPrevious": "{{{ \Lang::get('core::default.btn.prev') }}}", 
                        },
                        "sEmptyTable": "{{{ \Lang::get('core::default.table.empty') }}}",
                        "sSearch": "{{{ \Lang::get('core::default.table.search') }}} ",
                        "sInfo": "{{{ \Lang::get('core::default.table.showed') }}}",
                        "sInfoEmpty": "{{{ \Lang::get('core::default.table.empty.showed') }}}",
                        "sZeroRecords": "{{{ \Lang::get('core::default.table.empty.filtered') }}}",
                        "sInfoFiltered": "",
                    }
                }, param);

                jQuery('#' + param.domId).dataTable(param);

				jQuery(window).resize(function()
				{
					jQuery('#' + param.domId).css({ width: jQuery('#' + param.domId).parent().parent().parent().parent().width() });
				});

                return _this;
            }
        }
        
        var presentation = new presentationModelWizard();
			
        return presentation;
    });
	
	telenok.setPresentationObject({presentation: '{{$presentation}}'});
}
</script>

	
	
	 



@section('script')
	@parent
	
	@section('ajaxDone')
 
		jQuery.gritter.add({
			title: '{{{$controller->LL('notice.saved')}}} {{{$controller->LL('notice.saved.description')}}}',
			text: '{{{$controller->LL('notice.saved.thank.you')}}}',
			class_name: 'gritter-success gritter-light',
			time: 2000,
		});
		
		$el.closest('div.modal').html(data.tabContent); 

	@stop

@stop

<div class="modal-dialog">
	<div class="modal-content">

		<div class="modal-header table-header">
			<a class="close" data-dismiss="modal">×</a>
			<h3>{{{ \Telenok\Core\Model\Object\Type::where('code', $model->getTable())->first()->translate('title') }}}</h3>
		</div>


@section('notice')
	@parent
@stop


@section('form') 

{{ Form::model($model, array('url' => $routerParam, 'files' => true, 'id'=>"model-ajax-$uniqueId", 'class'=>'form-horizontal')) }}

		<div class="modal-body" style="max-height: 400px; overflow-y: auto; padding: 15px; position: relative;">
			<div class="widget-main">

				{{$controller->getFormContent($model, $type, $fields, $uniqueId)}}
					
			</div>
		</div>
		<div class="modal-footer">

			<div class="center no-margin">
				@if (\Input::get('chooseBtn'))
				<button class="btn btn-success" onclick="
					var $modal = jQuery(this).closest('.modal');
						$modal.data('model-data')( { id: '{{$model->getKey()}}', title: '{{{ $model->translate("title") }}}' } ); 
						$modal.modal('hide');
						return false;">
					<i class="fa fa-bullseye"></i>
					{{{ $controller->LL('btn.choose') }}}
				</button>
				@endif
				@if (\Input::get('saveBtn'))
				<button type="submit" class="btn btn-info">
					<i class="fa fa-floppy-o"></i>
					{{{ $controller->LL('btn.save') }}}
				</button>
				@endif
				<button class="btn" data-dismiss="modal">
					<i class="fa fa-floppy-o"></i>
					{{{ $controller->LL('btn.close') }}}
				</button>
			</div>


		</div>
	</div>
</div>
{{ Form::close() }}
@stop
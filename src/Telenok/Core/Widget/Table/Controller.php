<?php

namespace Telenok\Core\Widget\Table;

class Controller extends \Telenok\Core\Interfaces\Widget\Controller {

	protected $key = 'table';
	protected $parent = 'standart';
	protected $backendView = "core::widget.table.widget-backend";
	protected $row = 2;
	protected $col = 2;

	public function getInsertContent($id = '')
	{
		$widgetOnPage = \Telenok\Core\Model\Web\WidgetOnPage::findOrFail($id);

		if ($widgetOnPage->isWidgetLink())
		{
			$this->backendView = "core::module.web-page.widget-backend";

			return parent::getInsertContent($id);
		}

		$structure = $widgetOnPage->structure;

		$containerIds = $structure->get('containerIds');

		if (!$structure->has('row'))
		{
			$structure->put('row', $this->row);
		}

		if (!$structure->has('col'))
		{
			$structure->put('col', $this->col);
		}

		if (!$structure->has('containerIds') || (count($structure->get('containerIds', [])) < $structure->get('col') * $structure->get('row') ))
		{
			$ids = [];

			for ($row = 0; $row < $structure->get('row'); $row++)
			{
				for ($col = 0; $col < $structure->get('col'); $col++)
				{
					$ids["$row:$col"] = isset($ids["$row:$col"]) ? $ids["$row:$col"] : md5(uniqid("", true));
				}
			}

			$structure->put('containerIds', $ids);
			$widgetOnPage->structure = $structure->toArray();
			$widgetOnPage->save();
		}

		$rows = [];

		for ($r = 0; $r < $structure->get('row'); $r++)
		{
			for ($c = 0; $c < $structure->get('col'); $c++)
			{
				$container_id = $containerIds["$r:$c"];

				$rows[$r][$c] = ['container_id' => $containerIds["$r:$c"], 'content' => $this->getContainerContent($container_id)];
			}
		}

		return \View::make($this->getBackendView(), [
					'header' => $this->LL('header'),
					'title' => $widgetOnPage->title,
					'id' => $widgetOnPage->getKey(),
					'key' => $this->getKey(),
					'rows' => $rows,
					'widgetOnPage' => $widgetOnPage,
				])->render();
	}

	public function getContainerContent($container_id = "")
	{
		$content = [];

		$wOP = \Telenok\Core\Model\Web\WidgetOnPage::where('container', $container_id)->orderBy('order')->get();

		$widgetConfig = \App::make('telenok.config')->getWidget();

		$wOP->each(function($w) use (&$content, $widgetConfig)
		{
			$content[] = $widgetConfig->get($w->key)->getInsertContent($w->id);
		});

		return $content;
	}

	public function insertFromBufferOnPage($languageId = 0, $pageId = 0, $key = '', $id = 0, $container = '', $order = 0, $bufferId = 0)
	{
		$widgetOnPage = null;
		
		
		\DB::transaction(function() use ($languageId, $pageId, $key, $id, $container, $order, &$widgetOnPage, $bufferId)
		{
			$widgetOnPage = \Telenok\Core\Model\Web\WidgetOnPage::findOrFail($id);
			$buffer = \Telenok\Core\Model\System\Buffer::findOrFail($bufferId);

			if ($buffer->key == 'cut')
			{
				$widgetOnPage->storeOrUpdate([
					"container" => $container,
					"order" => $order,
					"key" => $key,
				]);

				$bufferWidget = \Telenok\Core\Model\System\Buffer::find($bufferId);

				if ($bufferWidget)
				{
					$bufferWidget->forceDelete();
				}
			}
			else if ($buffer->key == 'copy')
			{
				$widgetOnPage = \Telenok\Core\Model\Web\WidgetOnPage::findOrFail($id)->replicate();
				$widgetOnPage->push();
				$widgetOnPage->storeOrUpdate([
						"container" => $container,
						"order" => $order,
					]); 
			}
			else if ($buffer->key == 'copy-link')
			{
				try
				{
					$originalWidget = $this->findOriginalWidget($id);

					if ($originalWidget->isWidgetLink())
					{
						throw new \Exception();
					}
				}
				catch (\Exception $e)
				{
					throw new \Exception($this->LL('rror.widget.link.nonexistent'));
				}

				$widgetOnPage = $originalWidget->replicate();
				$widgetOnPage->push();
				$widgetOnPage->storeOrUpdate([
						"container" => $container,
						"order" => $order,
					]);

				$originalWidget->widgetLink()->save($widgetOnPage);
			}

			\Telenok\Core\Model\Web\WidgetOnPage::where("order", ">=", $order)
					->where("container", $container)->get()->each(function($item)
			{
				$item->storeOrUpdate(["order" => $item->order + 1]);
			});

			$widgetOnPage->widgetPage()->associate(\Telenok\Core\Model\Web\Page::findOrFail($pageId))->save(); 
			$widgetOnPage->widgetLanguageLanguage()->associate(\Telenok\Core\Model\System\Language::findOrFail($languageId))->save(); 
			$widgetOnPage->save();

			if ($buffer->key == 'cut' || $buffer->key == 'copy')
			{
				$this->copyAndInsertChild($widgetOnPage, $buffer);
			}
		});

		return $widgetOnPage;
	}

	public function copyAndInsertChild($widgetOnPage, $buffer)
	{  
		$structure = $widgetOnPage->structure; 

		$newContainres = [];

		foreach($structure->get('containerIds') as $key => $container)
		{ 
			$newContainres[$key] = md5(uniqid("", true));

			\Telenok\Core\Model\Web\WidgetOnPage::where("container", $container)->get()->each(function($item) use ($widgetOnPage, $buffer, $newContainres, $key)
			{
				$buffer = \Telenok\Core\Model\System\Buffer::addBuffer(\Auth::user()->getKey(), $item->getKey(), 'web-page', $buffer->key);
				
				$widget = \App::make('telenok.config')->getWidget()->get($item->key);
				
				$widget->insertFromBufferOnPage(
						$widgetOnPage->widgetLanguageLanguage()->first()->pluck('id'), 
						$widgetOnPage->widgetPage()->first()->pluck('id'), 
						$item->key, 
						$item->getKey(), 
						$newContainres[$key], 
						$item->order, 
						$buffer->getKey());
			});
		}

		$structure->put('containerIds', $newContainres);
		$widgetOnPage->structure = $structure->toArray();
		$widgetOnPage->save();
	}

	public function insertOnPage($languageId = 0, $pageId = 0, $key = '', $id = 0, $container = '', $order = 0)
	{
		$w = parent::insertOnPage($languageId, $pageId, $key, $id, $container, $order);

		$structure = $w->structure;

		if (!$structure->has('row'))
		{
			$structure->put('row', $this->row);
		}

		if (!$structure->has('col'))
		{
			$structure->put('col', $this->col);
		}

		if (!$structure->has('containerIds')/* || (count($structure->get('containerIds')) < $structure->get('row') * $structure->get('row') )*/)
		{
			$ids = [];

			for ($row = 0; $row < $structure->get('row'); $row++)
			{
				for ($col = 0; $col < $structure->get('col'); $col++)
				{
					$ids["$row:$col"] = isset($ids["$row:$col"]) ? $ids["$row:$col"] : md5(uniqid("", true));
				}
			}

			$structure->put('containerIds', $ids);
			$w->structure = $structure->toArray();
			$w->save();
		}

		return $w;
	}

	public function removeFromPage($id = 0)
	{
		$w = \Telenok\Core\Model\Web\WidgetOnPage::findOrFail($id);

		if (\Telenok\Core\Model\Web\WidgetOnPage::whereIn('container', $w->structure->get('containerIds', []))->count())
		{
			throw new \Exception($this->LL('widget.has.child'));
		}
		else
		{
			return parent::removeFromPage($id);
		}
	}
}

?>
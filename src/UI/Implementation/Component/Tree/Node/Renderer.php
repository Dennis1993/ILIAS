<?php
declare(strict_types=1);

/* Copyright (c) 2019 Nils Haagen <nils.haagen@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation\Component\Tree\Node;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Component\Tree\Node;

class Renderer extends AbstractComponentRenderer {
	/**
	 * @inheritdoc
	 */
	public function render(Component\Component $component, RendererInterface $default_renderer) {
		$this->checkComponent($component);

		$tpl_name = "tpl.node.html";
		$tpl = $this->getTemplate($tpl_name, true, true);

		$tpl->setVariable("LABEL", $component->getLabel());

		if($component->hasAsyncLoading()) {
			$tpl->setVariable("ASYNCURL", $component->getAsyncURL());
		}

		$triggered_signals = $component->getTriggeredSignals();

		if(count($triggered_signals) > 0) {
			$component = $this->triggerFurtherSignals($component, $triggered_signals);
		}

		$id = $this->bindJavaScript($component);
		$tpl->setVariable("ID", $id);

		$subnodes = $component->getSubnodes();

		if(count($subnodes) > 0 || $component->hasAsyncLoading()) {
			$tpl->touchBlock("expandable");
			if($component->isExpanded()) {
				$tpl->touchBlock("expanded");
			}
		}

		if(count($subnodes) > 0) {
			$subnodes_html = $default_renderer->render($subnodes);
			$tpl->setVariable("SUBNODES", $subnodes_html);
		}

		return $tpl->get();
	}


	protected function triggerFurtherSignals(Node\Node $component, array $triggered_signals)
	{
		$signals = [];
		foreach ($triggered_signals as $s) {
			$signals[] = [
				"signal_id" => $s->getSignal()->getId(),
				"event" => $s->getEvent(),
				"options" => $s->getSignal()->getOptions()
			];
		}
		$signals = json_encode($signals);

		return $component->withAdditionalOnLoadCode(function ($id) use ($signals) {
			return "
			$('#$id > span').click(function(e){
				var node = $('#$id'),
					signals = $signals;

				for (var i = 0; i < signals.length; i++) {
					var s = signals[i];
					node.trigger(s.signal_id, s);
				}

				return false;
			});";
		});
	}

	/**
	 * @inheritdoc
	 */
	protected function getComponentInterfaceName() {
		return array(
			Node\Simple::class
		);
	}
}

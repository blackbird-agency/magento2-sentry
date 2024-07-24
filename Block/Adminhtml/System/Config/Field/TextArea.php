<?php

namespace JustBetter\Sentry\Block\Adminhtml\System\Config\Field;

class TextArea extends \Magento\Framework\View\Element\Template
{
    /**
     * @return string
     */
    public function _toHtml()
    {
        $inputName = $this->getInputName();
        $column = $this->getColumn();

        return '<textarea id="' . $this->getInputId().'" name="' . $inputName . '" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '></textarea>';
    }
}

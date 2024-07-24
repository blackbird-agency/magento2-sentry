<?php

namespace JustBetter\Sentry\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class PageTypes extends AbstractFieldArray
{

    /**
     * @var TextArea
     */
    protected TextArea $controllerRenderer;

    protected TextArea $urlRenderer;


    protected function _prepareToRender(){
        $this->addColumn('name', ['label' => __('Name'), 'class' => 'required-entry']);
        $this->addColumn('urls', [
            'label' => __('Url Patterns'),
            'class' => 'required-entry',
            'comment' => __('One url pattern per line'),
            'renderer' => $this->getUrlRenderer()
        ]);
        $this->addColumn('controller_full_names', [
            'label' => __('Controller Full Names Patterns'),
            'class' => 'required-entry',
            'comment' => __('One controller pattern per line'),
            'renderer' => $this->getControllerRenderer()
        ]);
    }

    /**
     * @return TextArea
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getControllerRenderer()
    {
        if (!isset($this->controllerRenderer)) {
            $this->controllerRenderer = $this->getLayout()->createBlock(
                TextArea::class,
                ''
            );
        }
        return $this->controllerRenderer;
    }

    /**
     * @return TextArea
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getUrlRenderer()
    {
        if (!isset($this->urlRenderer)) {
            $this->urlRenderer = $this->getLayout()->createBlock(
                TextArea::class,
                ''
            );
        }
        return $this->urlRenderer;
    }


}

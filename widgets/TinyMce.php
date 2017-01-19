<?php

namespace yeesoft\media\widgets;

use pendalf89\tinymce\TinyMce as TinyMceWidget;
use yeesoft\media\assets\FileInputAsset;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class TinyMce extends InputWidget
{
    /**
     * @var string Optional, if set, only this image can be selected by user
     */
    public $thumb = '';

    /**
     * @var string JavaScript function, which will be called before insert file data to input.
     * Argument data contains file data.
     * data example: [alt: "Witch with cat", description: "123", url: "/uploads/2014/12/vedma-100x100.jpeg", id: "45"]
     */
    public $callbackBeforeInsert = '';

    /**
     * @var array the options for the TinyMCE JS plugin.
     * Please refer to the TinyMCE JS plugin Web page for possible options.
     * @see http://www.tinymce.com/wiki.php/Configuration
     */
    public $clientOptions = [
        'menubar' => false,
        'height' => 400,
        'image_dimensions' => true,
        'entity_encoding' => 'raw',
        'plugins' => [
            'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code contextmenu table wordcount pagebreak',
        ],
        'toolbar' => 'undo redo | styleselect bold italic | alignleft aligncenter alignright alignjustify bullist numlist outdent indent | pagebreak link image table | code',
    ];

    /**
     * @var string TinyMCE widget
     */
    private $tinyMCE = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->clientOptions['file_picker_callback'])) {
            $this->clientOptions['file_picker_callback'] = new JsExpression(
                'function(callback, value, meta) {
                    mediaTinyMCE(callback, value, meta);
                }'
            );
        }

        if (empty($this->clientOptions['document_base_url'])) {
            $this->clientOptions['document_base_url'] = '';
        }

        if (empty($this->clientOptions['convert_urls'])) {
            $this->clientOptions['convert_urls'] = false;
        }
		
		// Automatic translation of TinyMCE editor
		
		if (empty($this->clientOptions['language'])) {
            
			// From fr-FR (IETF language tag, defined in Yii config),
			// we move to fr_FR (TinyMCE language files format)
			$currentLanguageCode = str_replace('-', '_', \Yii::$app->language);
			
			if ($currentLanguageCode != 'en_US') {
				$tinyLangFiles = array_diff(scandir(\Yii::getAlias('@vendor/pendalf89/yii2-tinymce/langs')), ['..', '.']);
				
				foreach ($tinyLangFiles as $fileName) {
					if ($currentLanguageCode == $fileName) {
						// A lang file exists named as that language code
						$this->clientOptions['language'] = $currentLanguageCode;
						break;
					}	
				}
				
				if (empty($this->clientOptions['language'])) {
					// The language code may be of the form 'fr_FR'
					$arr = explode('_', $currentLanguageCode);
					$shortLanguageCode = $arr[0];
					
					foreach ($tinyLangFiles as $fileName) {
						if (strpos($fileName, $shortLanguageCode) !== FALSE) {
							$this->clientOptions['language'] = substr($fileName, 0, strlen($fileName) - 3);
							break;
						}
					}
				}
			}
        }

        $this->tinyMCE = TinyMceWidget::widget([
            'name' => $this->name,
            'model' => $this->model,
            'attribute' => $this->attribute,
            'clientOptions' => $this->clientOptions,
            'options' => $this->options,
        ]);
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        FileInputAsset::register($this->view);

        if (!empty($this->callbackBeforeInsert)) {
            $this->view->registerJs('
                $("#' . $this->options['id'] . '").on("fileInsert", ' . $this->callbackBeforeInsert . ');'
            );
        }

        $modal = $this->renderFile('@vendor/yeesoft/yii2-yee-media/views/manage/modal.php',
            [
                'inputId' => $this->options['id'],
                'btnId' => $this->options['id'] . '-btn',
                'frameId' => $this->options['id'] . '-frame',
                'frameSrc' => Url::to(['/media/manage']),
                'thumb' => $this->thumb,
            ]);

        return $this->tinyMCE . $modal;
    }
}
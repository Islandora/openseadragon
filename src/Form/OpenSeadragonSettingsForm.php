<?php

namespace Drupal\openseadragon\Form;

use Drupal\views\Views;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openseadragon\ConfigInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OpenSeadragon Settings Form.
 *
 * TODO: Some of these settings could be moved to the display level.
 */
class OpenSeadragonSettingsForm extends ConfigFormBase {

  /**
   * OpenSeadragon Config.
   *
   * @var \Drupal\openseadragon\ConfigInterface
   */
  private $seadragonConfig;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\openseadragon\ConfigInterface $seadragon_config
   *   A OpenSeadragon config object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigInterface $seadragon_config) {
    $this->setConfigFactory($config_factory);
    $this->seadragonConfig = $seadragon_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('openseadragon.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openseadragon.admin_settings.form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openseadragon.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $tlrb_map = array_combine(
      [
        'TOP_RIGHT',
        'TOP_LEFT',
        'BOTTOM_LEFT',
        'BOTTOM_RIGHT',
        'ABSOLUTE',
      ],
      [
        'TOP_RIGHT',
        'TOP_LEFT',
        'BOTTOM_LEFT',
        'BOTTOM_RIGHT',
        'ABSOLUTE',
      ]
    );

    $settings = $this->seadragonConfig->getSettings();

    $form['image_server_settings'] = [
      '#type' => 'details',
      '#title' => t('IIIF Server Settings'),
      '#open' => TRUE,
      'iiif_server' => [
        '#type' => 'textfield',
        '#title' => t('IIIF Image server location'),
        '#default_value' => $this->seadragonConfig->getIiifAddress(),
        '#required' => TRUE,
        '#description' => t('Please enter the image server location without trailing slash. eg:  http://www.example.org/iiif/2.'),
      ],
      'manifest_view' => [
        '#type' => 'select',
        '#title' => t('IIIF Manifest View'),
        '#empty_value' => TRUE,
        '#default_value' => $this->seadragonConfig->getManifestView(),
        '#description' => t('If using a view to generate IIIF manifests, please select it here.'),
        '#options' => Views::getViewsAsOptions(TRUE),
      ],
    ];
    $form['openseadragon_settings'] = [
      '#type' => 'details',
      '#title' => t('OpenSeadragon Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
      'fit_to_aspect_ratio' => [
        '#type' => 'checkbox',
        '#title' => t('Constrain image to viewport'),
        '#default_value' => $settings['fit_to_aspect_ratio'],
        '#description' => t('On the initial page load, the entire image will be visible in the viewport.'),
      ],
      // We don't provide "id" as configurable to users.
      // We don't provide "element" as configurable to users.
      // We don't provide "tileSources" as configurable to users.
      'tabIndex' => [
        '#type' => 'textfield',
        '#title' => t('Tab Index'),
        '#default_value' => $settings['tabIndex'],
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#size' => 10,
        '#description' => t('Tabbing order index to assign to the viewer element. Positive values are selected in increasing order. When tabIndex is 0 source order is used. A negative value omits the viewer from the tabbing order.'),
      ],
      'debugMode' => [
        '#type' => 'checkbox',
        '#title' => t('Debug mode'),
        '#default_value' => $settings['debugMode'],
        '#description' => t('Toggles whether messages should be logged and fail-fast behavior should be provided.'),
      ],
      'debugGridColor' => [
        '#type' => 'textfield',
        '#title' => t('Debug Grid Color'),
        '#default_value' => $settings['debugGridColor'],
        '#description' => t('Color of the grid in debug mode.'),
      ],
      'blendTime' => [
        '#type' => 'textfield',
        '#title' => t('Blend time'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['blendTime'],
        '#description' => t('Specifies the duration of animation as higher or lower level tiles are replacing the existing tile.'),
      ],
      'alwaysBlend' => [
        '#type' => 'checkbox',
        '#title' => t('Always blend'),
        '#default_value' => $settings['alwaysBlend'],
        '#description' => t("Forces the tile to always blend. By default the tiles skip blending when the blendTime is surpassed and the current animation frame would not complete the blend."),
      ],
      'autoHideControls' => [
        '#type' => 'checkbox',
        '#title' => t('Auto-hide controls'),
        '#default_value' => $settings['autoHideControls'],
        '#description' => t("If the user stops interacting with the viewport, fade the navigation controls. Useful for presentation since the controls are by default floated on top of the image the user is viewing."),
      ],
      'immediateRender' => [
        '#type' => 'checkbox',
        '#title' => t('Immediate render'),
        '#default_value' => $settings['immediateRender'],
        '#description' => t('Render the best closest level first, ignoring the lowering levels which provide the effect of very blurry to sharp. It is recommended to change setting to true for mobile devices.'),
      ],
      'defaultZoomLevel' => [
        '#type' => 'textfield',
        '#title' => t('Default zoom level'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['defaultZoomLevel'],
        '#description' => t('Zoom level to use when image is first opened or the home button is clicked. If 0, adjusts to fit viewer.'),
      ],
      'opacity' => [
        '#type' => 'textfield',
        '#title' => t('Opacity'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['opacity'],
        '#description' => t('Default opacity of the tiled images (1=opaque, 0=transparent)'),
      ],
      'compositeOperation' => [
        '#type' => 'select',
        '#title' => t('Composite Operation'),
        '#description' => t('How the image is composited onto other images.'),
        '#default_value' => $settings['compositeOperation'],
        '#options' => array_combine(
          [
            NULL,
            'source-over',
            'source-atop',
            'source-in',
            'source-out',
            'destination-over',
            'destination-atop',
            'destination-in',
            'destination-out',
            'lighter',
            'copy',
            'xor',
          ],
          [
            '',
            'source-over',
            'source-atop',
            'source-in',
            'source-out',
            'destination-over',
            'destination-atop',
            'destination-in',
            'destination-out',
            'lighter',
            'copy',
            'xor',
          ]
        ),
      ],
      'placeholderFillStyle' => [
        '#type' => 'textfield',
        '#title' => t('Placeholder Fill Style'),
        '#default_value' => $settings['placeholderFillStyle'],
        '#description' => t('Draws a colored rectangle behind the tile if it is not loaded yet. You can pass a CSS color value like "#FF8800".'),
      ],
      'degrees' => [
        '#type' => 'textfield',
        '#title' => t('Initial Rotation'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['degrees'],
        '#description' => t('Initial rotation in degrees.'),
      ],
      'minZoomLevel' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Zoom Level'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['minZoomLevel'],
        '#description' => t('Minimum Zoom Level (integer).'),
      ],
      'maxZoomLevel' => [
        '#type' => 'textfield',
        '#title' => t('Maximum Zoom Level'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['maxZoomLevel'],
        '#description' => t('Maximum Zoom Level (integer).'),
      ],
      'homeFillsViewer' => [
        '#type' => 'checkbox',
        '#title' => t('Home Button Fills Viewer'),
        '#default_value' => $settings['homeFillsViewer'],
        '#description' => t('Make the "home" button fill the viewer and clip the image, instead of fitting the image to the viewer and letterboxing.'),
      ],
      'panHorizontal' => [
        '#type' => 'checkbox',
        '#title' => t('Pan horizontal'),
        '#default_value' => $settings['panHorizontal'],
        '#description' => t('Allow horizontal pan.'),
      ],
      'panVertical' => [
        '#type' => 'checkbox',
        '#title' => t('Pan vertical'),
        '#default_value' => $settings['panVertical'],
        '#description' => t('Allow vertical pan.'),
      ],
      'constrainDuringPan' => [
        '#type' => 'checkbox',
        '#title' => t('Constrain During Pan'),
        '#default_value' => $settings['constrainDuringPan'],
      ],
      'wrapHorizontal' => [
        '#type' => 'checkbox',
        '#title' => t('Wrap horizontal'),
        '#default_value' => $settings['wrapHorizontal'],
        '#description' => t('Set to true to force the image to wrap horizontally within the viewport. Useful for maps or images representing the surface of a sphere or cylinder.'),
      ],
      'wrapVertical' => [
        '#type' => 'checkbox',
        '#title' => t('Wrap vertical'),
        '#default_value' => $settings['wrapVertical'],
        '#description' => t('Set to true to force the image to wrap vertically within the viewport. Useful for maps or images representing the surface of a sphere or cylinder.'),
      ],
      'minZoomImageRatio' => [
        '#type' => 'textfield',
        '#title' => t('Minimum zoom image ratio'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['minZoomImageRatio'],
        '#description' => t('The minimum percentage ( expressed as a number between 0 and 1 ) of the viewport height or width at which the zoom out will be constrained. Setting it to 0, for example will allow you to zoom out infinity.'),
      ],
      'maxZoomPixelRatio' => [
        '#type' => 'textfield',
        '#title' => t('Maximum zoom pixel ratio'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['maxZoomPixelRatio'],
        '#description' => t('The maximum ratio to allow a zoom-in to affect the highest level pixel ratio. This can be set to Infinity to allow "infinite" zooming into the image though it is less effective visually if the HTML5 Canvas is not available on the viewing device.'),
      ],
      'smoothTileEdgesMinZoom' => [
        '#type' => 'textfield',
        '#title' => t('Smooth Tile Edges Minimum Zoom'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['smoothTileEdgesMinZoom'],
        '#description' => t('A zoom percentage ( where 1 is 100% ) of the highest resolution level. When zoomed in beyond this value alternative compositing will be used to smooth out the edges between tiles. This will have a performance impact. Can be set to Infinity to turn it off. Note: This setting is ignored on iOS devices due to a known bug (See <a href="https://github.com/openseadragon/openseadragon/issues/952">https://github.com/openseadragon/openseadragon/issues/952</a>).'),
      ],
      // We don't provide "iOSDevice" as configurable to users.
      'autoResize' => [
        '#type' => 'checkbox',
        '#title' => t('Auto Resize'),
        '#default_value' => $settings['autoResize'],
        '#description' => t('Set to false to prevent polling for viewer size changes. Useful for providing custom resize behavior.'),
      ],
      'preserveImageSizeOnResize' => [
        '#type' => 'checkbox',
        '#title' => t('Preserve Image Size On Resize'),
        '#default_value' => $settings['preserveImageSizeOnResize'],
        '#description' => t('Set to true to have the image size preserved when the viewer is re-sized. This requires Auto Resize to be enabled (default).'),
      ],
      'minScrollDeltaTime' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Scroll Delta Time'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['minScrollDeltaTime'],
        '#description' => t('Number of milliseconds between canvas-scroll events. This value helps normalize the rate of canvas-scroll events between different devices, causing the faster devices to slow down enough to make the zoom control more manageable.'),
      ],
      'pixelsPerWheelLine' => [
        '#type' => 'textfield',
        '#title' => t('Pixels Per Wheel Line'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['pixelsPerWheelLine'],
        '#description' => t('For pixel-resolution scrolling devices, the number of pixels equal to one scroll line.'),
      ],
      'visibilityRatio' => [
        '#type' => 'textfield',
        '#title' => t('Visibility ratio'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['visibilityRatio'],
        '#description' => t("The percentage ( as a number from 0 to 1 ) of the source image which must be kept within the viewport. If the image is dragged beyond that limit, it will 'bounce' back until the minimum visibility ratio is achieved. Setting this to 0 and wrapHorizontal ( or wrapVertical ) to true will provide the effect of an infinitely scrolling viewport."),
      ],
      'imageLoaderLimit' => [
        '#type' => 'textfield',
        '#title' => t('Image loader limit'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['imageLoaderLimit'],
        '#description' => t('The maximum number of image requests to make concurrently. By default it is set to 0 allowing the browser to make the maximum number of image requests in parallel as allowed by the browsers policy.'),
      ],
      'clickTimeThreshold' => [
        '#type' => 'textfield',
        '#title' => t('Click time threshold'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['clickTimeThreshold'],
        '#description' => t('The number of milliseconds within which a pointer down-up event combination will be treated as a click gesture.'),
      ],
      'clickDistThreshold' => [
        '#type' => 'textfield',
        '#title' => t('Click distance threshold'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['clickDistThreshold'],
        '#description' => t('The maximum distance allowed between a pointer down event and a pointer up event to be treated as a click gesture.'),
      ],
      'dblClickTimeThreshold' => [
        '#type' => 'textfield',
        '#title' => t('Double click distance threshold'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['dblClickTimeThreshold'],
        '#description' => t('The number of milliseconds within which two pointer down-up event combinations will be treated as a double-click gesture.'),
      ],
      'dblClickDistThreshold' => [
        '#type' => 'textfield',
        '#title' => t('Double click distance threshold'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['dblClickDistThreshold'],
        '#description' => t('The maximum distance allowed between two pointer click events to be treated as a double-click gesture.'),
      ],
      'springStiffness' => [
        '#type' => 'textfield',
        '#title' => t('Spring stiffness'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['springStiffness'],
        '#description' => t('Determines how sharply the springs used for animations move.'),
      ],
      'animationTime' => [
        '#type' => 'textfield',
        '#title' => t('Animation time'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['animationTime'],
        '#description' => t('Specifies the animation duration per each OpenSeadragon.Spring which occur when the image is dragged or zoomed.'),
      ],
      'gestureSettingsMouse' => [
        '#type' => 'details',
        '#title' => t('Mouse Pointer Gesture Settings'),
        '#open' => FALSE,
        '#description' => t('<p>Settings for gestures generated by a mouse pointer device. (See <a href="https://openseadragon.github.io/docs/OpenSeadragon.html#.GestureSettings">OpenSeadragon.GestureSettings</a>)</p>'),
        'scrollToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Scroll To Zoom'),
          '#default_value' => $settings['gestureSettingsMouse']['scrollToZoom'],
          '#description' => t('Zoom on scroll gesture.'),
          '#return_value' => TRUE,
        ],
        'clickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Click To Zoom'),
          '#default_value' => $settings['gestureSettingsMouse']['clickToZoom'],
          '#description' => t('Zoom on click gesture.'),
        ],
        'dblClickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Double Click To Zoom'),
          '#default_value' => $settings['gestureSettingsMouse']['dblClickToZoom'],
          '#description' => t('Zoom on double-click gesture. Note: If set to true then clickToZoom should be set to false to prevent multiple zooms.'),
        ],
        'pinchToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch To Zoom'),
          '#default_value' => $settings['gestureSettingsMouse']['pinchToZoom'],
          '#description' => t('Zoom on pinch gesture.'),
        ],
        'flickEnabled' => [
          '#type' => 'checkbox',
          '#title' => t('Flick Gesture'),
          '#default_value' => $settings['gestureSettingsMouse']['flickEnabled'],
          '#description' => t('Enable flick gesture.'),
        ],
        'flickMinSpeed' => [
          '#type' => 'textfield',
          '#title' => t('Flick Minimum Speed'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsMouse']['flickMinSpeed'],
          '#description' => t('If flickEnabled is true, the minimum speed to initiate a flick gesture (pixels-per-second).'),
        ],
        'flickMomentum' => [
          '#type' => 'textfield',
          '#title' => t('Flick Momentum'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsMouse']['flickMomentum'],
          '#description' => t('If flickEnabled is true, the momentum factor for the flick gesture.'),
        ],
        'pinchRotate' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch Rotate'),
          '#default_value' => $settings['gestureSettingsMouse']['pinchRotate'],
          '#description' => t('If pinchRotate is true, the user will have the ability to rotate the image using their fingers.'),
        ],
      ],
      'gestureSettingsTouch' => [
        '#type' => 'details',
        '#title' => t('Touch Pointer Gesture Settings'),
        '#open' => FALSE,
        '#description' => t('<p>Settings for gestures generated by a touch pointer device. (See <a href="https://openseadragon.github.io/docs/OpenSeadragon.html#.GestureSettings">OpenSeadragon.GestureSettings</a>)</p>'),
        'scrollToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Scroll To Zoom'),
          '#default_value' => $settings['gestureSettingsTouch']['scrollToZoom'],
          '#description' => t('Zoom on scroll gesture.'),
          '#return_value' => TRUE,
        ],
        'clickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Click To Zoom'),
          '#default_value' => $settings['gestureSettingsTouch']['clickToZoom'],
          '#description' => t('Zoom on click gesture.'),
        ],
        'dblClickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Double Click To Zoom'),
          '#default_value' => $settings['gestureSettingsTouch']['dblClickToZoom'],
          '#description' => t('Zoom on double-click gesture. Note: If set to true then clickToZoom should be set to false to prevent multiple zooms.'),
        ],
        'pinchToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch To Zoom'),
          '#default_value' => $settings['gestureSettingsTouch']['pinchToZoom'],
          '#description' => t('Zoom on pinch gesture.'),
        ],
        'flickEnabled' => [
          '#type' => 'checkbox',
          '#title' => t('Flick Gesture'),
          '#default_value' => $settings['gestureSettingsTouch']['flickEnabled'],
          '#description' => t('Enable flick gesture.'),
        ],
        'flickMinSpeed' => [
          '#type' => 'textfield',
          '#title' => t('Flick Minimum Speed'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsTouch']['flickMinSpeed'],
          '#description' => t('If flickEnabled is true, the minimum speed to initiate a flick gesture (pixels-per-second).'),
        ],
        'flickMomentum' => [
          '#type' => 'textfield',
          '#title' => t('Flick Momentum'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsTouch']['flickMomentum'],
          '#description' => t('If flickEnabled is true, the momentum factor for the flick gesture.'),
        ],
        'pinchRotate' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch Rotate'),
          '#default_value' => $settings['gestureSettingsMouse']['pinchRotate'],
          '#description' => t('If pinchRotate is true, the user will have the ability to rotate the image using their fingers.'),
        ],
      ],
      'gestureSettingsPen' => [
        '#type' => 'details',
        '#title' => t('Pen Pointer Gesture Settings'),
        '#open' => FALSE,
        '#description' => t('<p>Settings for gestures generated by a pen pointer device. (See <a href="https://openseadragon.github.io/docs/OpenSeadragon.html#.GestureSettings">OpenSeadragon.GestureSettings</a>)</p>'),
        'scrollToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Scroll To Zoom'),
          '#default_value' => $settings['gestureSettingsPen']['scrollToZoom'],
          '#description' => t('Zoom on scroll gesture.'),
          '#return_value' => TRUE,
        ],
        'clickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Click To Zoom'),
          '#default_value' => $settings['gestureSettingsPen']['clickToZoom'],
          '#description' => t('Zoom on click gesture.'),
        ],
        'dblClickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Double Click To Zoom'),
          '#default_value' => $settings['gestureSettingsPen']['dblClickToZoom'],
          '#description' => t('Zoom on double-click gesture. Note: If set to true then clickToZoom should be set to false to prevent multiple zooms.'),
        ],
        'pinchToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch To Zoom'),
          '#default_value' => $settings['gestureSettingsPen']['pinchToZoom'],
          '#description' => t('Zoom on pinch gesture.'),
        ],
        'flickEnabled' => [
          '#type' => 'checkbox',
          '#title' => t('Flick Gesture'),
          '#default_value' => $settings['gestureSettingsPen']['flickEnabled'],
          '#description' => t('Enable flick gesture.'),
        ],
        'flickMinSpeed' => [
          '#type' => 'textfield',
          '#title' => t('Flick Minimum Speed'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsPen']['flickMinSpeed'],
          '#description' => t('If flickEnabled is true, the minimum speed to initiate a flick gesture (pixels-per-second).'),
        ],
        'flickMomentum' => [
          '#type' => 'textfield',
          '#title' => t('Flick Momentum'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsPen']['flickMomentum'],
          '#description' => t('If flickEnabled is true, the momentum factor for the flick gesture.'),
        ],
        'pinchRotate' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch Rotate'),
          '#default_value' => $settings['gestureSettingsMouse']['pinchRotate'],
          '#description' => t('If pinchRotate is true, the user will have the ability to rotate the image using their fingers.'),
        ],
      ],
      'gestureSettingsUnknown' => [
        '#type' => 'details',
        '#title' => t('Unknown Pointer Gesture Settings'),
        '#open' => FALSE,
        '#description' => t('<p>Settings for gestures generated by a unknown pointer device. (See <a href="https://openseadragon.github.io/docs/OpenSeadragon.html#.GestureSettings">OpenSeadragon.GestureSettings</a>)</p>'),
        'scrollToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Scroll To Zoom'),
          '#default_value' => $settings['gestureSettingsUnknown']['scrollToZoom'],
          '#description' => t('Zoom on scroll gesture.'),
          '#return_value' => TRUE,
        ],
        'clickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Click To Zoom'),
          '#default_value' => $settings['gestureSettingsUnknown']['clickToZoom'],
          '#description' => t('Zoom on click gesture.'),
        ],
        'dblClickToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Double Click To Zoom'),
          '#default_value' => $settings['gestureSettingsUnknown']['dblClickToZoom'],
          '#description' => t('Zoom on double-click gesture. Note: If set to true then clickToZoom should be set to false to prevent multiple zooms.'),
        ],
        'pinchToZoom' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch To Zoom'),
          '#default_value' => $settings['gestureSettingsUnknown']['pinchToZoom'],
          '#description' => t('Zoom on pinch gesture.'),
        ],
        'flickEnabled' => [
          '#type' => 'checkbox',
          '#title' => t('Flick Gesture'),
          '#default_value' => $settings['gestureSettingsUnknown']['flickEnabled'],
          '#description' => t('Enable flick gesture.'),
        ],
        'flickMinSpeed' => [
          '#type' => 'textfield',
          '#title' => t('Flick Minimum Speed'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsUnknown']['flickMinSpeed'],
          '#description' => t('If flickEnabled is true, the minimum speed to initiate a flick gesture (pixels-per-second).'),
        ],
        'flickMomentum' => [
          '#type' => 'textfield',
          '#title' => t('Flick Momentum'),
          '#size' => 10,
          '#element_validate' => [[$this, 'elementValidateNumber']],
          '#default_value' => $settings['gestureSettingsPen']['flickMomentum'],
          '#description' => t('If flickEnabled is true, the momentum factor for the flick gesture.'),
        ],
        'pinchRotate' => [
          '#type' => 'checkbox',
          '#title' => t('Pinch Rotate'),
          '#default_value' => $settings['gestureSettingsMouse']['pinchRotate'],
          '#description' => t('If pinchRotate is true, the user will have the ability to rotate the image using their fingers.'),
        ],
      ],
      'zoomPerClick' => [
        '#type' => 'textfield',
        '#title' => t('Zoom per click'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['zoomPerClick'],
        '#description' => t('The "zoom distance" per mouse click or touch tap. Note: Setting this to 1.0 effectively disables the click-to-zoom feature (also see gestureSettings[Mouse|Touch|Pen].clickToZoom/dblClickToZoom).'),
      ],
      'zoomPerScroll' => [
        '#type' => 'textfield',
        '#title' => t('Zoom per scroll'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['zoomPerScroll'],
        '#description' => t('The "zoom distance" per mouse scroll or touch pinch. Note: Setting this to 1.0 effectively disables the mouse-wheel zoom feature (also see gestureSettings[Mouse|Touch|Pen].scrollToZoom}).'),
      ],
      'zoomPerSecond' => [
        '#type' => 'textfield',
        '#title' => t('Zoom per second'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['zoomPerSecond'],
        '#description' => t('The number of seconds to animate a single zoom event over.'),
      ],
      'navigatorOptions' => [
        '#type' => 'fieldset',
        '#title' => t('Navigator options'),
        'showNavigator' => [
          '#type' => 'checkbox',
          '#title' => t('Show Navigator'),
          '#default_value' => $settings['showNavigator'],
          '#description' => t('Set to true to make the navigator minimap appear.'),
          '#return_value' => TRUE,
        ],
        'navigatorContainer' => [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="openseadragon_settings[navigatorOptions][showNavigator]"]' => ['checked' => TRUE],
            ],
          ],
          // We don't provide "navigatorId" as configurable to users.
          'navigatorPosition' => [
            '#type' => 'select',
            '#title' => t('Navigator Position'),
            '#description' => t('If "ABSOLUTE" is specified, then navigator[Top|Left|Height|Width] determines the size and position of the navigator minimap in the viewer, and navigatorSizeRatio and navigatorMaintainSizeRatio are ignored. For "TOP_LEFT", "TOP_RIGHT", "BOTTOM_LEFT", and "BOTTOM_RIGHT", the navigatorSizeRatio or navigator[Height|Width] values determine the size of the navigator minimap.'),
            '#default_value' => $settings['navigatorPosition'],
            '#options' => $tlrb_map,
          ],
          'navigatorSizeRatio' => [
            '#type' => 'textfield',
            '#title' => t('Navigator Size Ratio'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['navigatorSizeRatio'],
            '#description' => t('Ratio of navigator size to viewer size. Ignored if navigator[Height|Width] are specified.'),
          ],
          'navigatorMaintainSizeRatio' => [
            '#type' => 'checkbox',
            '#title' => t('Navigator Maintain Size Ration'),
            '#default_value' => $settings['navigatorMaintainSizeRatio'],
            '#description' => t('If true, the navigator minimap is resized (using navigatorSizeRatio) when the viewer size changes.'),
          ],
          'navigatorTop' => [
            '#type' => 'textfield',
            '#title' => t('Navigator Top Position'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['navigatorTop'],
            '#description' => t('Specifies the location of the navigator minimap (see Navigator Position).'),
          ],
          'navigatorLeft' => [
            '#type' => 'textfield',
            '#title' => t('Navigator Left Position'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['navigatorLeft'],
            '#description' => t('Specifies the location of the navigator minimap (see Navigator Position).'),
          ],
          'navigatorHeight' => [
            '#type' => 'textfield',
            '#title' => t('Navigator Height'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['navigatorHeight'] ,
            '#description' => t('Specifies the size of the navigator minimap (see Navigator Position). If specified, Navigator Size Ratio and Navigator Maintain Size Ratio are ignored.'),
          ],
          'navigatorWidth' => [
            '#type' => 'textfield',
            '#title' => t('Navigator Width'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['navigatorWidth'],
            '#description' => t('Specifies the size of the navigator minimap (see Navigator Position). If specified, Navigator Size Ratio and Navigator Maintain Size Ratio are ignored.'),
          ],
          'navigatorAutoResize' => [
            '#type' => 'checkbox',
            '#title' => t('Navigator Auto Resize'),
            '#default_value' => $settings['navigatorAutoResize'],
            '#description' => t('Set to false to prevent polling for navigator size changes. Useful for providing custom resize behavior. Setting to false can also improve performance when the navigator is configured to a fixed size.'),
          ],
          'navigatorAutoFade' => [
            '#type' => 'checkbox',
            '#title' => t('Navigator Auto Fade'),
            '#default_value' => $settings['navigatorAutoFade'],
            '#description' => t('If the user stops interacting with the viewport, fade the navigator minimap. Setting to false will make the navigator minimap always visible.'),
          ],
          'navigatorAutoFade' => [
            '#type' => 'checkbox',
            '#title' => t('Navigator Auto Fade'),
            '#default_value' => $settings['navigatorAutoFade'],
            '#description' => t('If the user stops interacting with the viewport, fade the navigator minimap. Setting to false will make the navigator minimap always visible.'),
          ],
          'navigatorRotate' => [
            '#type' => 'checkbox',
            '#title' => t('Navigator Rotate'),
            '#default_value' => $settings['navigatorRotate'],
            '#description' => t('If true, the navigator will be rotated together with the viewer.'),
          ],
        ],
      ],
      'controlsFadeDelay' => [
        '#type' => 'textfield',
        '#title' => t('Controls Fade Delay'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['controlsFadeDelay'],
        '#description' => t('The number of milliseconds to wait once the user has stopped interacting with the interface before begining to fade the controls. Assumes showNavigationControl and autoHideControls are both true.'),
      ],
      'controlsFadeLength' => [
        '#type' => 'textfield',
        '#title' => t('Controls Fade Length'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['controlsFadeLength'],
        '#description' => t('The number of milliseconds to animate the controls fading out.'),
      ],
      'maxImageCacheCount' => [
        '#type' => 'textfield',
        '#title' => t('Max Image Cache Count'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['maxImageCacheCount'],
        '#description' => t('The max number of images we should keep in memory (per drawer).'),
      ],
      'timeout' => [
        '#type' => 'textfield',
        '#title' => t('Timeout'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['timeout'],
      ],
      'useCanvas' => [
        '#type' => 'checkbox',
        '#title' => t('Use Canvas'),
        '#default_value' => $settings['useCanvas'],
        '#description' => t('Set to false to not use an HTML canvas element for image rendering even if canvas is supported.'),
      ],
      'minPixelRatio' => [
        '#type' => 'textfield',
        '#title' => t('Minimum Pixel Ratio'),
        '#size' => 10,
        '#element_validate' => [[$this, 'elementValidateNumber']],
        '#default_value' => $settings['minPixelRatio'],
        '#description' => t('The higher the minPixelRatio, the lower the quality of the image that is considered sufficient to stop rendering a given zoom level. For example, if you are targeting mobile devices with less bandwith you may try setting this to 1.5 or higher.'),
      ],
      'mouseNavEnabled' => [
        '#type' => 'checkbox',
        '#title' => t('Enable Mouse Navigation'),
        '#default_value' => $settings['mouseNavEnabled'],
        '#description' => t('Is the user able to interact with the image via mouse or touch. Default interactions include dragging the image in a plane, and zooming in toward and away from the image.'),
      ],
      'navigationOptions' => [
        '#type' => 'fieldset',
        '#title' => t('Navigation Controls'),
        'showNavigationControl' => [
          '#type' => 'checkbox',
          '#title' => t('Show Navigation Control'),
          '#default_value' => $settings['showNavigationControl'],
          '#description' => t('Set to false to prevent the appearance of the default navigation controls. Note that if set to false, the customs buttons set by the options zoomInButton, zoomOutButton etc, are rendered inactive.'),
          '#return_value' => TRUE,
        ],
        'navigationContainer' => [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="openseadragon_settings[navigationOptions][showNavigationControl]"]' => ['checked' => TRUE],
            ],
          ],
          'navigationControlAnchor' => [
            '#type' => 'select',
            '#title' => t('Navigation Control Anchor'),
            '#default_value' => $settings['navigationControlAnchor'],
            '#description' => t('Placement of the default navigation controls. To set the placement of the sequence controls, see the sequenceControlAnchor option.'),
            '#options' => $tlrb_map,
          ],
          'showZoomControl' => [
            '#type' => 'checkbox',
            '#title' => t('Show Zoom Control'),
            '#default_value' => $settings['showZoomControl'],
            '#description' => t('If true then + and - buttons to zoom in and out are displayed. Note: OpenSeadragon.Options.showNavigationControl is overriding this setting when set to false.'),
          ],
          'showHomeControl' => [
            '#type' => 'checkbox',
            '#title' => t('Show Home Control'),
            '#default_value' => $settings['showHomeControl'],
            '#description' => t('documentation'),
          ],
          'showFullPageControl' => [
            '#type' => 'checkbox',
            '#title' => t('Show Full Page Control'),
            '#default_value' => $settings['showFullPageControl'],
            '#description' => t('If true then the rotate left/right controls will be displayed as part of the standard controls. This is also subject to the browser support for rotate (e.g., viewer.drawer.canRotate()). Note: OpenSeadragon.Options.showNavigationControl is overriding this setting when set to false.'),
          ],
          'showRotationControl' => [
            '#type' => 'checkbox',
            '#title' => t('Show Rotation Control'),
            '#default_value' => $settings['showRotationControl'],
            '#description' => t('If sequenceMode is true, then provide buttons for navigating forward and backward through the images.'),
          ],
        ],
      ],
      'sequenceControlAnchor' => [
        '#type' => 'select',
        '#title' => t('Sequence Control Anchor'),
        '#default_value' => $settings['sequenceControlAnchor'],
        '#description' => t('Placement of the default sequence controls.'),
        '#options' => $tlrb_map,
      ],
      'navPrevNextWrap' => [
        '#type' => 'checkbox',
        '#title' => t('Navigation Previous/Next Wrap'),
        '#default_value' => $settings['navPrevNextWrap'],
        '#description' => t('If true then the "previous" button will wrap to the last image when viewing the first image and the "next" button will wrap to the first image when viewing the last image.'),
      ],
      // Sequence mode is autodetected and used as the default when
      // multiple tilesources are present. It is overridden by
      // collection mode.
      // We don't provide "zoomInButton" as configurable to users.
      // We don't provide "zoomOutButton" as configurable to users.
      // We don't provide "homeButton" as configurable to users.
      // We don't provide "fullPageButton" as configurable to users.
      // We don't provide "rotateLeftButton" as configurable to users.
      // We don't provide "rotateRightButton" as configurable to users.
      // We don't provide "previousButton" as configurable to users.
      // We don't provide "nextButton" as configurable to users.
      'sequenceOptions' => [
        '#type' => 'fieldset',
        '#title' => 'Sequence Mode',
        'sequenceMode' => [
          '#type' => 'item',
          '#description' => 'Default mode if multiple images are detected.  Images are viewed one at a time with arrow buttons for navigation. Enabling Collection Mode will disable Sequence Mode.',
        ],
        'sequenceContainer' => [
          '#type' => 'container',
          '#description' => t('Default mode if multiple tile sources are to be displayed.  Images will be viewed one at a time with arrow buttons for navigation.  Enabling Collection Mode will override Sequence Mode.'),
          '#states' => [
            'enabled' => [
              ':input[name="openseadragon_settings[collectionModeFields][collectionMode]"]' => ['checked' => FALSE],
            ],
          ],
          // We don't provide "initialPage" as configurable to users.
          'preserveViewport' => [
            '#type' => 'checkbox',
            '#title' => t('Preserve View-port'),
            '#default_value' => $settings['preserveViewport'],
            '#description' => t('If sequenceMode is true, then normally navigating through each image resets the viewport to "home" position. If preserveViewport is set to true, then the viewport position is preserved when navigating between images in the sequence.'),
          ],
          'preserveOverlays' => [
            '#type' => 'checkbox',
            '#title' => t('Preserve Overlays'),
            '#default_value' => $settings['preserveOverlays'],
            '#description' => t('If sequenceMode is true, then normally navigating through each image resets the overlays. If preserveOverlays is set to true, then the overlays added with OpenSeadragon.Viewer#addOverlay are preserved when navigating between images in the sequence. Note: setting preserveOverlays overrides any overlays specified in the global "overlays" option for the Viewer. It\'s also not compatible with specifying per-tileSource overlays via the options, as those overlays will persist even after the tileSource is closed.'),
          ],
          'showReferenceStrip' => [
            '#type' => 'checkbox',
            '#title' => t('Show Reference Strip'),
            '#default_value' => $settings['showReferenceStrip'],
            '#description' => t('If sequenceMode is true, then display a scrolling strip of image thumbnails for navigating through the images.'),
          ],
          'referenceStripContainer' => [
            '#type' => 'container',
            '#states' => [
              'visible' => [
                ':input[name="openseadragon_settings[sequenceOptions][showReferenceStrip]"]' => ['checked' => TRUE],
              ],
            ],
            'referenceStripScroll' => [
              '#type' => 'select',
              '#title' => t('Reference Strip Scroll'),
              '#default_value' => $settings['referenceStripScroll'],
              '#description' => t('Display the reference strip horizontally or vertically.'),
              '#options' => array_combine([
                'horizontal',
                'vertical',
              ],
                [
                  'horizontal',
                  'vertical',
                ]
              ),
            ],
            // We don't provide "referenceStripElement" as configurable
            // to users.
            'referenceStripHeight' => [
              '#type' => 'textfield',
              '#title' => t('Reference Strip Height'),
              '#size' => 10,
              '#element_validate' => [[$this, 'elementValidateNumber']],
              '#default_value' => $settings['referenceStripHeight'],
              '#description' => t('Height of the reference strip in pixels.'),
            ],
            'referenceStripWidth' => [
              '#type' => 'textfield',
              '#title' => t('Reference Strip Width'),
              '#size' => 10,
              '#element_validate' => [[$this, 'elementValidateNumber']],
              '#default_value' => $settings['referenceStripWidth'],
              '#description' => t('Width of the reference strip in pixels.'),
            ],
            'referenceStripPosition' => [
              '#type' => 'textfield',
              '#title' => t('Reference Strip Position'),
              '#default_value' => $settings['referenceStripPosition'],
              '#description' => t('The position of the reference strip.'),
              '#options' => $tlrb_map,
            ],
            'referenceStripSizeRatio' => [
              '#type' => 'textfield',
              '#title' => t('Reference Strip Size Ratio'),
              '#size' => 10,
              '#element_validate' => [[$this, 'elementValidateNumber']],
              '#default_value' => $settings['referenceStripSizeRatio'],
              '#description' => t('Ratio of reference strip size to viewer size.'),
            ],
          ],
        ],
      ],
      'collectionModeFields' => [
        '#type' => 'fieldset',
        '#title' => t('Collection Mode'),
        'collectionMode' => [
          '#type' => 'checkbox',
          '#title' => t('Enable Collection Mode'),
          '#default_value' => $settings['collectionMode'],
          '#description' => t('Arranges multiple images in a grid or line. Enabling Collection Mode will disable Sequence Mode.'),
        ],
        'collectionModeContainer' => [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="openseadragon_settings[collectionModeFields][collectionMode]"]' => ['checked' => TRUE],
            ],
          ],
          'collectionRows' => [
            '#type' => 'textfield',
            '#title' => t('Collection Rows'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['collectionRows'],
            '#description' => t('If collectionMode is true, specifies how many rows the grid should have. Use 1 to make a line. If collectionLayout is "vertical", specifies how many columns instead.'),
          ],
          'collectionColumns' => [
            '#type' => 'textfield',
            '#title' => t('Collection Columns'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['collectionColumns'],
            '#description' => t('If collectionMode is true, specifies how many columns the grid should have. Use 1 to make a line. If collectionLayout is "vertical", specifies how many rows instead. Ignored if collectionRows is not set to a falsy value.'),
          ],
          'collectionLayout' => [
            '#type' => 'select',
            '#title' => t('Collection Layout'),
            '#default_value' => $settings['collectionLayout'],
            '#description' => t('If collectionMode is true, specifies whether to arrange vertically or horizontally.'),
            '#options' => array_combine([
              'horizontal',
              'vertical',
            ],
              [
                'horizontal',
                'vertical',
              ]
            ),
          ],
          'collectionTileSize' => [
            '#type' => 'textfield',
            '#title' => t('Collection Tile Size'),
            '#size' => 10,
            '#default_value' => $settings['collectionTileSize'],
            '#description' => t('If collectionMode is true, specifies the size, in viewport coordinates, for each TiledImage to fit into. The TiledImage will be centered within a square of the specified size.'),
          ],
          'collectionTileMargin' => [
            '#type' => 'textfield',
            '#title' => t('Collection Tile Margin'),
            '#size' => 10,
            '#element_validate' => [[$this, 'elementValidateNumber']],
            '#default_value' => $settings['collectionTileMargin'],
            '#description' => t('If collectionMode is true, specifies the margin, in viewport coordinates, between each TiledImage.'),
          ],
        ],
      ],
      // We don't provide "crossOriginPolicy" as configurable to users.
      // We don't provide "ajaxWithCredentials" as configurable to users.
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('iiif_server'))) {
      $server = $form_state->getValue('iiif_server');
      if (!UrlHelper::isValid($server, UrlHelper::isExternal($server))) {
        $form_state->setErrorByName('iiif_server', "IIIF Server address is not a valid URL");
      }
      elseif (!$this->validateIiifUrl($server)) {
        $form_state->setErrorByName('iiif_server', "IIIF Server does not seem to be accessible.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('openseadragon.settings');
    $this->normalizeSettings($form_state->getValue('openseadragon_settings'));
    // Get default to match array formatting.
    $default_settings = $this->seadragonConfig->getDefaultSettings();
    $this->filterSettings($form_state->getValue('openseadragon_settings'), $default_settings);
    $config->set('viewer_options', $form_state->getValue('openseadragon_settings'));

    if (!empty($form_state->getValue('iiif_server'))) {
      $config->set('iiif_server', $form_state->getValue('iiif_server'));
      $config->set('manifest_view', $form_state->getValue('manifest_view'));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Ensure the IIIF server is accessible.
   *
   * @param string $server_uri
   *   The absolute or relative URI to the server.
   *
   * @return bool
   *   True if server returns 200 on a HEAD request.
   */
  private function validateIiifUrl($server_uri) {
    global $base_url;
    $client = \Drupal::httpClient();
    if (!UrlHelper::isExternal($server_uri)) {
      $server_uri = $base_url . $server_uri;
    }
    try {
      $result = $client->head($server_uri);
      return ($result->getStatusCode() == 200);
    }
    catch (ClientException $e) {
      return FALSE;
    }

  }

  /**
   * Validate that the element is a number.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   * @param array $complete_form
   *   The complete form.
   *
   * @return bool
   *   True if a number, false otherwise.
   */
  public function elementValidateNumber(array &$element, FormStateInterface $form_state, array &$complete_form) {
    return (is_numeric($form_state->getValue($element)));
  }

  /**
   * Casts the settings to appropriate types so they work in javascript.
   *
   * @param array $settings
   *   The Openseadragon settings to be normalized.
   *
   * @return array
   *   Normalized settings.
   */
  private function normalizeSettings(array &$settings) {
    foreach ($settings as $key => $value) {
      $settings[$key] = $this->normalizeSetting($value);
    }
    return $settings;
  }

  /**
   * Normalizes the given setting.
   *
   * @param mixed $value
   *   The setting to be normalized.
   *
   * @return array|float|int|string
   *   The normalized setting.
   */
  private function normalizeSetting($value) {
    if (is_array($value)) {
      return $this->normalizeSettings($value);
    }
    elseif (filter_var($value, FILTER_VALIDATE_INT) !== FALSE) {
      return (int) $value;
    }
    elseif (filter_var($value, FILTER_VALIDATE_FLOAT) !== FALSE) {
      return (float) $value;
    }
    elseif (filter_var($value, FILTER_VALIDATE_URL) !== FALSE) {
      return check_plain($value);
    }
    return $value;
  }

  /**
   * Casts the settings to appropriate types so they work in javascript.
   *
   * @param array $settings
   *   The Openseadragon settings to be normalized.
   * @param array $default_settings
   *   The default settings used to determine valid array keys.
   *
   * @return array
   *   Normalized settings.
   */
  private function filterSettings(array &$settings, array $default_settings) {
    foreach ($settings as $key => $value) {
      // We don't want the nested containers in the settings
      // so we strip them out.
      if (is_array($value) && !isset($default_settings[$key])) {
        $tmp = $this->filterSettings($value, $default_settings);
        $settings = array_merge($settings, $tmp);
        unset($settings[$key]);
      }
      elseif (is_array($value)) {
        $settings[$key] = $this->filterSettings($value, $default_settings[$key]);
      }
      if (is_string($value) && empty($value)) {
        unset($settings[$key]);
      }
      elseif (is_null($value)) {
        unset($settings[$key]);
      }
    }
    return $settings;
  }

}

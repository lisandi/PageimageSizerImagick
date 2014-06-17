<?php
/*******************************************************************************
  *  ImagickResizer
  *
  * ---------------------------------------------------------------------------
  *  @version     -   '0.3.1'
  *  @date        -   $Date: 2014/06/17 17:26:50 $
  *  @licence     -   GNU GPL v2 - http://www.gnu.org/licenses/gpl-2.0.html
  * ---------------------------------------------------------------------------
  *  $Source: /WEB/pw4/htdocs/site/modules/PageimageSizerImagick/PageimageSizerImagickConfig.php,v $
  *  $Id: PageimageSizerImagickConfig.php,v 1.1.2.2 2014/06/17 17:26:50 horst Exp $
  ******************************************************************************
**/


class PageimageSizerImagickConfig extends Wire {

    public function getConfig(array $data) {

        // check that they have the required PW version
        if(version_compare(wire('config')->version, '2.4.3', '<')) {
            $this->error(" requires ProcessWire 2.4.3 or newer. Please update.");
        }

        $modules = wire('modules');
        $gdInfo = function_exists('gd_info') ? gd_info() : array("GD Version"=>'');
        $form = new InputfieldWrapper();

        // if Imagick extension is not installed we notify the user and return
        if(!ImagickInfo::hasImagick()) {
            // we have no full support, lets check if the Extension is available
            if(!ImagickInfo::hasImagick(false)) {
                $field = $modules->get("InputfieldMarkup");
                $field->attr('name', 'info1');
                $field->collapsed = Inputfield::collapsedNo;
                $field->attr('value',
                    "<span style='font-weight:bold; color:red;'>Imagick is not available! <br />You need to install the Imagick PHP Extension (and ImageMagick) on your server to use this module.</span> (<a href='https://processwire.com/talk/topic/6096-imagick-resizer-need-tests-for-icc-cms/#entry59711' target='_blank'>read more</a>)</span><br /><br />" .
                    "GD-lib available: " . (!function_exists('imagecreatefromjpeg') ? "No<br />" : "Yes - PHP " . phpversion() . " - {$gdInfo["GD Version"]}<br />" ) .
                    "Can read EXIF data: " . (function_exists('exif_read_data') ? 'Yes' : 'No')
                    );
                $field->label = __('Information about available image functions on this server');
                $field->columnWidth = 100;
                $form->add($field);
                return $form;
            }
            // Extension is installed but it lacks of some functions
            $missingMethods = ImagickInfo::compareNeededMethods();
            $missingMethods = '<ul><li>' . implode('</li><li>', $missingMethods) . '</li></ul>';
            $field = $modules->get("InputfieldMarkup");
            $field->attr('name', 'info1');
            $field->collapsed = Inputfield::collapsedNo;
            $field->attr('value',
                "<br /><span style='font-weight:bold; color:red;'>Imagick is installed, but it lacks supporting some functionality! <br />missing functions are:<br />" .
                $missingMethods .
                "</span><br />" .
                "GD-lib available: " . (!function_exists('imagecreatefromjpeg') ? "No<br />" : "Yes - PHP " . phpversion() . " - {$gdInfo["GD Version"]}<br />" ) .
                "Can read EXIF data: " . (function_exists('exif_read_data') ? 'Yes' : 'No')
                );
            $field->label = __('Information about available image functions on this server');
            $field->columnWidth = 100;
            $form->add($field);
            return $form;
        }


        // Imagick extension is available, proceed the config page
        $folders = array('targetprofiles', 'workspaceprofiles', 'cmykprofiles', 'grayscaleprofiles');
        $targetprofiles = $workspaceprofiles = $cmykprofiles = $grayscaleprofiles = array();
        clearstatcache();
        $dir = dirname(__FILE__) . '/icc/';
        foreach($folders as $folder) {
            if(is_dir($dir . $folder . '/')) {
                if($dh = opendir($dir . $folder . '/')) {
                    while (($file = readdir($dh)) !== false) {
                        if(is_file($dir . $folder . '/' . $file) && 'icc' == strtolower( pathinfo($dir . $folder . '/' . $file, PATHINFO_EXTENSION))) {
                            ${$folder}["$file"] = $file;
                        }
                    }
                    closedir($dh);
                }
            }
            sort(${$folder});
            $icc = array();
            foreach(${$folder} as $k=>$v) {
                $icc["$v"] = $v;
            }
            ${$folder} = $icc;
        }
        unset($v, $icc, $dir, $dh, $folders, $folder, $file);
        $rgbinprofiles = array_merge($targetprofiles, $workspaceprofiles);

        $imInfo = '<p>';
        foreach(ImagickInfo::getImageMagickInfos() as $k=>$v) {
            $imInfo .= "$k: $v<br />";
        }
        $imInfo .= '</p>';
        unset($k, $v);


        $defaults = wire('config')->imageSizerOptions;
        $field = $modules->get("InputfieldMarkup");
        $field->attr('name', 'info1');
        $field->collapsed = Inputfield::collapsedYes;
        $field->attr('value',
            "Imagick available: " . (!class_exists('Imagick') ? "No<br />" : "Yes - " . str_replace(' http://www.imagemagick.org', '', ImagickInfo::getImageMagickVersion(true)) . "<br />") .
            "GD-lib available: " . (!function_exists('imagecreatefromjpeg') ? "No<br />" : "Yes - PHP " . phpversion() . " - {$gdInfo["GD Version"]}<br />" ) .
            "Can read EXIF data: " . (function_exists('exif_read_data') ? 'Yes' : 'No') . "<br /><br />" .
            'Your default $config->imageSizerOptions settings in site/config.php are:' . "<br />" .
            'quality: ' . "{$defaults['quality']}<br />" .
            'cropping: ' . "{$defaults['cropping']}<br />" .
            'upscaling: ' . "{$defaults['upscaling']}<br />" .
            'sharpening: ' . "{$defaults['sharpening']}<br />" .
            'autoRotation: ' . "{$defaults['autoRotation']}" .
            "<br /><br />$imInfo"
            );
        unset($defaults, $gdInfo, $imInfo);
        $field->label = __('Information about available image functions on this server');
        $field->columnWidth = 100;
        $form->add($field);


        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Metadata');
        $fieldset->attr('name', '_metadata');
        $fieldset->collapsed = Inputfield::collapsedYes;

            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name', 'keepEXIF');
            $field->attr('value', 1);
            $field->attr('checked', $data['keepEXIF'] ? 'checked' : '');
            $field->columnWidth = 33;
            $field->label = __('Keep EXIF data');
            $field->description = __('Check this if you want to keep all EXIF data within the resized images versions');
            $field->notes = __('Attention: increases filesize!');
            $fieldset->add($field);

            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name', 'keepXMP');
            $field->attr('value', 1);
            $field->attr('checked', $data['keepXMP'] ? 'checked' : '');
            $field->columnWidth = 33;
            $field->label = __('Keep XMP data');
            $field->description = __('Check this if you want to keep all XMP data within the resized images versions');
            $field->notes = __('Attention: increases filesize!');
            $fieldset->add($field);

            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name', 'keepICC');
            $field->attr('value', 1);
            $field->attr('checked', $data['keepICC'] ? 'checked' : '');
            $field->columnWidth = 34;
            $field->label = __('Keep ICC-Profiles with image variations');
            $field->description = __('Leave it unchecked to avoid increasing filesize. Check it if this doesn\'t matter or if you use CMS with a different target colorspace than sRGB_Color_Space_Profile.icc');
            $fieldset->add($field);

        $form->add($fieldset);


//        $fieldset = $modules->get('InputfieldFieldset');
//        $fieldset->label = __('CMS - ICC Color Management System');
//        $fieldset->attr('name', '_advanced_cms_check');
//        $fieldset->collapsed = Inputfield::collapsedYes;
//
//            $field = $modules->get("InputfieldMarkup");
//            $field->attr('name', 'info2');
//            $field->collapsed = Inputfield::collapsedNo;
//            $field->attr('value', 'The ICC/CMS functionality will be added / enabled with the next release.');
//            $field->label = __('');
//            $field->columnWidth = 100;
//            $fieldset->add($field);
//
//            $field = $modules->get('InputfieldCheckbox');
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'useCMS');
//            $field->attr('value', 1);
//            $field->attr('checked', $data['useCMS'] ? 'checked' : '');
//            $field->columnWidth = 100;
//            $field->label = __('Check this to use Color Management System with ICC-Profiles');
//            $field->description = __('');
//            $fieldset->add($field);
//
//            $field = $modules->get("InputfieldSelect");
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'rgbOutProfile');
//            $field->attr('value', $data['rgbOutProfile']);
//            $field->label = __('RGB Out Profile');
//            $field->addOptions($targetprofiles);
//            $field->description = __("Select the Target RGB Profile");
//            $field->notes = __("default: sRGB_Color_Space_Profile.icc");
//            $field->columnWidth = 33;
//            $fieldset->add($field);
//
//            $field = $modules->get("InputfieldSelect");
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'grayOutProfile');
//            $field->attr('value', $data['grayOutProfile']);
//            $field->label = __('Grayscale Out Profile');
//            $field->addOptions($grayscaleprofiles);
//            $field->description = __("Select the Target Grayscale Profile");
//            $field->notes = __("default: grayGamma18.icc");
//            $field->columnWidth = 33;
//            $fieldset->add($field);
//
//            $field = $modules->get("InputfieldSelect");
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'workspaceProfile');
//            $field->attr('value', $data['workspaceProfile']);
//            $field->label = __('Workspace Profile');
//            $field->addOptions($workspaceprofiles);
//            $field->description = __("Select the Workspace Profile");
//            $field->notes = __("default: AdobeRGB1998.icc");
//            $field->columnWidth = 34;
//            $fieldset->add($field);
//
//            $field = $modules->get("InputfieldSelect");
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'rgbInProfile');
//            $field->attr('value', $data['rgbInProfile']);
//            $field->label = __('RGB In Profile');
//            $field->addOptions($rgbinprofiles);
//            $field->description = __("Select a default profile for RGB files without an ICC profile");
//            $field->notes = __("default: sRGB_Color_Space_Profile.icc");
//            $field->columnWidth = 33;
//            $fieldset->add($field);
//
//            $field = $modules->get("InputfieldSelect");
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'grayInProfile');
//            $field->attr('value', $data['grayInProfile']);
//            $field->label = __('Grayscale In Profile');
//            $field->addOptions($grayscaleprofiles);
//            $field->description = __("Select a default profile for grayscale files without an ICC profile");
//            $field->notes = __("default: grayGamma18.icc");
//            $field->columnWidth = 33;
//            $fieldset->add($field);
//
//            $field = $modules->get("InputfieldSelect");
//            $field->showIf = "canWeUseCMS=1";
//            $field->attr('name', 'cmykInProfile');
//            $field->attr('value', $data['cmykInProfile']);
//            $field->label = __('CMYK In Profile');
//            $field->addOptions($cmykprofiles);
//            $field->description = __("Select a default profile for color separated files without an ICC profile");
//            $field->notes = __("default: USWebCoatedSWOP.icc");
//            $field->columnWidth = 34;
//            $fieldset->add($field);
//
//        $form->add($fieldset);


        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = __('Advanced');
        $fieldset->attr('name', '_advanced');
        $fieldset->collapsed = Inputfield::collapsedYes;

            $field = $modules->get("InputfieldSelect");
            $field->attr('name', 'gdFallback');
            $field->attr('value', $data['gdFallback']);
            $field->label = __('Fallback to GD-lib');
            $field->addOptions(array(
                '1' => 'Silent fallback if ImageMagick isn\'t available (default)',
                '2' => 'Do not use GD-lib, throw error instead'
                ));
            $field->description = __('This determines the behavior if ImageMagick is not available. The default is to fallback to PHP\'s built-in GD library, but you can choose to throw an error instead.');
            $field->columnWidth = 100;
            $fieldset->add($field);

        $form->add($fieldset);

        return $form;
    }

}


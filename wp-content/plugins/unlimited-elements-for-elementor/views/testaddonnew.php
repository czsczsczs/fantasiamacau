<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');


class UniteCreatorTestAddonNewView{
	
	protected $showToolbar = true;
	protected $showHeader = true;
	protected $addon;
	protected $addonID;
	protected $isPreviewMode;	
	protected $isTestData1;
	protected $textSingle, $textPlural;
	
	
	/**
	 * constructor
	 */
	public function __construct(){
		
		$this->putHtml();
	}
	
	
	/**
	 * get header text
	 */
	protected function getHeader(){
		
		$addonTitle = $this->addon->getTitle();
		
		$headerTitle = esc_html__("Test ","unlimited-elements-for-elementor").$this->textSingle;
		$headerTitle .= " - ".$addonTitle;
		
		return($headerTitle);
	}
	
	
	/**
	 * put header html
	 */
	protected function putHeaderHtml(){
		
		$headerTitle = $this->getHeader();
		require HelperUC::getPathTemplate("header");
		
	}
	
	/**
	 * init by addon type
	 */
	private function initByAddonType($objType){
		
		$this->textSingle = $objType->textSingle;
		$this->textPlural = $objType->textPlural;
		
	}
	
	
	/**
	 * init by addon
	 */
	private function initByAddon($addonID){
		
		if(empty($addonID))
			UniteFunctionsUC::throwError("Addon ID not given");
		
		$this->addonID = $addonID;
		
		$addon = new UniteCreatorAddon();
		$addon->initByID($addonID);
		
		$this->addon = $addon;
		
		$objType = $addon->getObjAddonType();
		
		$this->initByAddonType($objType);
		
	}
	
	
	/**
	 * put html
	 */
	private function putHtml(){
		
		//HelperHtmlUC::putAddonTypesBrowserDialogs();
		
		$addonID = UniteFunctionsUC::getGetVar("id","",UniteFunctionsUC::SANITIZE_ID);
		
		$this->initByAddon($addonID);
		
		$objAddons = new UniteCreatorAddons();
		
		$isNeedHelperEditor = $objAddons->isHelperEditorNeeded($this->addon);
		
		
		$addonTitle = $this->addon->getTitle();
		
		$addonType = $this->addon->getType();
		$objAddonType = $this->addon->getObjAddonType();
		
		
		$urlEditAddon = HelperUC::getViewUrl_EditAddon($addonID);
		
		$urlTestWithData = HelperUC::getViewUrl_TestAddon($addonID, "loaddata=test");
		
		//init addon config
		$addonConfig = new UniteCreatorAddonConfig();
		$addonConfig->setStartAddon($this->addon);
		
		$this->isTestData1 = $this->addon->isTestDataExists(1);
		
		//get addon data
		$addonData = null;
		$isLoadData = UniteFunctionsUC::getGetVar("loaddata","",UniteFunctionsUC::SANITIZE_NOTHING);
		
		if($isLoadData == "test" && $this->isTestData1 == true)
			$addon->setValuesFromTestData(1);
		
		$isPreviewMode = UniteFunctionsUC::getGetVar("preview","",UniteFunctionsUC::SANITIZE_KEY);
		$isPreviewMode = UniteFunctionsUC::strToBool($isPreviewMode);
		
		$addonConfig->startWithPreview($isPreviewMode);
		
		$this->isPreviewMode = $isPreviewMode;

		if($this->showHeader)
			$this->putHeaderHtml();
		else
			require HelperUC::getPathTemplate("header_missing");
		
		$slot1AddHtml = "";
		if($this->isTestData1 == false)
			$slot1AddHtml = "style='display:none'";
		
		
		$styleShow = "";
		$styleHide = "style='display:none'";
		
		$urlBack = HelperUC::getViewUrl_Addons($addonType);
		if(!empty($objAddonType->addonView_urlBack))
			$urlBack = $objAddonType->addonView_urlBack;
		
		$textEditThis = esc_html__("Edit This ", "unlimited-elements-for-elementor"). $this->textSingle;
		
		$textBackTo = esc_html__("Back To ", "unlimited-elements-for-elementor"). $this->textPlural .esc_html__(" List", "unlimited-elements-for-elementor");
		
?>

<div id="uc_testaddon_wrapper" class="uc-testaddon-wrapper">

<?php if($this->showToolbar):?>

<div class="uc-testaddon-panel">
		
		<a href="<?php echo esc_attr($urlEditAddon)?>" class="unite-button-secondary" ><?php echo $textEditThis?></a>
		<a class="unite-button-secondary uc-button-cat-sap" href="<?php echo esc_attr($urlBack)?>"><?php esc_html_e($textBackTo, "unlimited-elements-for-elementor");?></a>
		
		<a id="uc_button_preview" href="javascript:void(0)" class="unite-button-secondary" <?php echo UniteProviderFunctionsUC::escAddParam($isPreviewMode?$styleHide:$styleShow)?>><?php esc_html_e("To Preview", "unlimited-elements-for-elementor")?></a>
		<a id="uc_button_close_preview" href="javascript:void(0)" class="unite-button-secondary" <?php echo UniteProviderFunctionsUC::escAddParam($isPreviewMode?$styleShow:$styleHide)?>><?php esc_html_e("Hide Preview", "unlimited-elements-for-elementor")?></a>
		
		<a id="uc_button_preview_tab" href="javascript:void(0)" class="unite-button-secondary uc-button-cat-sap"><?php esc_html_e("Preview New Tab", "unlimited-elements-for-elementor")?></a>
		
		<span id="uc_testaddon_slot1" class="uc-testaddon-slot" <?php echo UniteProviderFunctionsUC::escAddParam($slot1AddHtml)?>>
			<a id="uc_testaddon_button_restore" href="javascript:void(0)" class="unite-button-secondary"><?php esc_html_e("Restore Data", "unlimited-elements-for-elementor")?></a>
			<span id="uc_testaddon_loader_restore" class="loader-text" style="display:none"><?php esc_html_e("loading...")?></span>
			<a id="uc_testaddon_button_delete" href="javascript:void(0)" class="unite-button-secondary"><?php esc_html_e("Delete Data", "unlimited-elements-for-elementor")?></a>
			<span id="uc_testaddon_loader_delete" class="loader-text" style="display:none"><?php esc_html_e("deleting...")?></span>
		</span>
		
		<a id="uc_testaddon_button_save" href="javascript:void(0)" class="unite-button-secondary"><?php esc_html_e("Save Data", "unlimited-elements-for-elementor")?></a>
		<span id="uc_testaddon_loader_save" class="loader-text" style="display:none"><?php esc_html_e("saving...")?></span>
		
		<a id="uc_testaddon_button_clear" href="javascript:void(0)" class="unite-button-secondary"><?php esc_html_e("Clear", "unlimited-elements-for-elementor")?></a>
	
</div>

<?php endif; ?>

<form name="form_test_addon">

<?php 

	//put helper editor if needed
	
	if($isNeedHelperEditor)
		UniteProviderFunctionsUC::putInitHelperHtmlEditor();

    $addonConfig->putHtmlFrame(); 
?>

</form>

</div>

<script type="text/javascript">

	jQuery(document).ready(function(){

		var objTestAddonView = new UniteCreatorTestAddon();
		objTestAddonView.init();

		<?php if($isPreviewMode == true):?>
		jQuery("#uc_button_preview").trigger("click");
		<?php endif?>
		
	});
	
		
</script>


		<?php 
		
		
			
				
	}
	
	
}


new UniteCreatorTestAddonNewView();


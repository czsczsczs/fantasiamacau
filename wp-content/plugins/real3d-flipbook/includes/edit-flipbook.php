<?php 
   if ( ! defined( 'ABSPATH' ) ) {
       exit; // Exit if accessed directly
   }
   ?>
<div class='wrap'>
   <div id='real3dflipbook-admin' style="display:none;">
      <a href="admin.php?page=real3d_flipbook_admin" class="back-to-list-link">&larr; 
      <?php _e('Back to flipbooks list', 'flipbook'); ?>
      </a>
      <form method="post" id="real3dflipbook-options-form" enctype="multipart/form-data" action="admin-ajax.php?page=real3d_flipbook_admin&action=save_settings&bookId=<?php echo($current_id);?>">
         <h1><span id="edit-flipbook-text"></span></h1>
         <div id="titlediv">
            <div id="titlewrap">
               <input type="text" name="name" size="30" value="" id="title" spellcheck="true" autocomplete="off" placeholder="Enter title here">
            </div>
         </div>
         <div>
            <h2 id="r3d-tabs" class="nav-tab-wrapper wp-clearfix">
               <a href="#" class="nav-tab" data-tab="tab-pages">Pages</a>
               <a href="#" class="nav-tab" data-tab="tab-toc">Table of Contents</a>
               <a href="#" class="nav-tab" data-tab="tab-general">General</a>
               <a href="#" class="nav-tab" data-tab="tab-lightbox">Lightbox</a>
               <a href="#" class="nav-tab" data-tab="tab-webgl">WebGL</a>
               <a href="#" class="nav-tab" data-tab="tab-mobile">Mobile</a>
               <a href="#" class="nav-tab" data-tab="tab-ui">UI</a>
               <a href="#" class="nav-tab" data-tab="tab-menu">Menu Buttons</a>
               <a href="#" class="nav-tab" data-tab="tab-translate">Translate</a>
            </h2>
            <div id="tab-pages" style="display:none;">
               <p>Add pages to flipbook. Source can be PDF or images.</p>
               <br/>
               <h3>PDF Flipbook</h3>
               <p class="description" id="">Create flipbook from PDF. Select a PDF file or enter PDF file URL. (PDF needs to be on the same domain.)</p>
               <a class="add-pdf-pages-button button-primary" href="#">Select PDF</a><input type="text" class="regular-text" name="pdfUrl" value="" placeholder="PDF url"><button type="button" class="button-secondary preview-pdf-pages">Preview PDF pages</button>
               <br/>
               <br/>
               <h3>JPG Flipbook</h3>
               <p class="description" id="">Create flipbook from images. Select images to use as flipbook pages. Multiple file upload is enabled. </p>
               <a class="add-jpg-pages-button button-primary" href="#">Select images</a><br/><br/>

               <p class="pdf-to-jpg-info"></p>
               <table  style="" class="form-table" id="flipbook-pdf-options">
                  <tbody></tbody>
               </table>
               <div>
                  <!-- <h2 style="display:none;">Select flipbook type</h2> -->
                  <p style="display:none;">
                     <label>
                     <input id="flipbook-type-pdf" name="type" type="radio" value="pdf"> PDF     
                     </label>
                  </p>
                  <p style="display:none;">
                     <label>
                     <input id="flipbook-type-jpg" name="type" type="radio" value="jpg"> JPG     
                     </label>
                  </p>
  
                  <table  style="display:none;" class="form-table" id="flipbook-pdf-options">
                     <tbody></tbody>
                  </table>
                  <h3 style="display:none;">Pages </h3>
                  <div class="attachments-browser">
                     <div class="media-sidebar">
                        <div tabindex="0" data-id="-1" class="attachment-details">
                           <h2>Edit page</h2>
                           <div id="edit-page-canvas"></div>
                           <img id="edit-page-img" src="" draggable="false" alt="">
                           <div class="details">
                              <button type="button" class="button-secondary replace-page">Replace image</button>
                           </div>
                           <label class="setting" data-setting="title">
                           <input id="edit-page-title" type="text" value="" placeholder="Title (for Table of Contents)">
                           </label>
                           <label class="setting" data-setting="html-content">
                           <textarea id="edit-page-html-content" placeholder="HTML content - supports any HTML content, inline CSS and Javascript"></textarea>
                           </label>
                        </div>
                     </div>
                     <ul id="pages-container" tab
                        index="-1" class="attachments ui-sortable">
                     </ul>
                     <div class="delete-pages-button">Delete all pages</div>
                  </div>
               </div>
               <div id="convert-pdf" style="display:none;">PDF
                  <a href="#" class="add-new-h2 select-pdf-button">Select</a>
               </div>
            </div>
            <div id="tab-toc" style="display:none;">
               <p class="description">
               <p>Create custom Table of Contents. This overrides default PDF outline or table of contents created by page titles.</p>
               </p>
               <p>
                  <a class="add-toc-item button-primary" href="#">Add item</a>
                  <a href="#" type="button" class="button-link toc-delete-all">Delete all</a>
               </p>
               <table class="form-table" id="flipbook-toc-options">
                  <tbody></tbody>
               </table>
               <div id="toc-items" tabindex="-1" class="attachments ui-sortable"></div>
            </div>
            <div id="tab-general" style="display:none;">
               <table class="form-table" id="flipbook-general-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-normal"  style="display:none;">
               <table class="form-table" id="flipbook-normal-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-mobile"  style="display:none;">
               <p class="description">
               <p>Override settings for mobile devices (use different view mode, smaller textures ect)</p>
               </p>
               <table class="form-table" id="flipbook-mobile-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-lightbox"  style="display:none;">
               <table class="form-table" id="flipbook-lightbox-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-webgl"  style="display:none;">
               <table class="form-table" id="flipbook-webgl-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-ui"  style="display:none;">
               <div id="poststuff">
                  <div class="meta-box-sortables">

                     <table class="form-table" id="flipbook-ui-options">
                        <tbody></tbody>
                     </table>
                     <h3>Advanced settings</h3>
                     <p>Override layout and skin settings</p>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Skin</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Skin</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-skin-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Flipbook background</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Flipbook background</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-bg-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Top Menu</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Top Menu</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-bar-2-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Bottom Menu</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Bottom Menu</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-bar-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Buttons</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Buttons</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-buttons-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Floating buttons (on transparent menu)</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Floating buttons (on transparent menu)</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-menu-floating-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Side navigation buttons</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Side navigation buttons</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-side-buttons-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Close lightbox button</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Close lightbox button</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-close-button-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>


                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Sidebar</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Sidebar</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-sidebar-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div id="tab-translate"  style="display:none;">
               <table class="form-table" id="flipbook-translate-options">
                  <tbody></tbody>
               </table>
            </div>
            <div id="tab-menu"  style="display:none;">
               <div id="poststuff">
                  <div class="meta-box-sortables">
                     <h3>Menu buttons</h3>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Current page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Current page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-currentPage-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: First page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>First page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnFirst-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Previous page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Previous page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnPrev-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Next page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Next page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnNext-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Last page</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Last page</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnLast-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Autoplay</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Autoplay</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnAutoplay-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Zoom in</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Zoom in</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnZoomIn-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Zoom out</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Zoom out</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnZoomOut-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Table of Contents</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Table of Contents</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnToc-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Thumbnails</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Thumbnails</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnThumbs-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnShare-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Print</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Print</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnPrint-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Download pages</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Download pages</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnDownloadPages-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Download PDF</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Download PDF</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnDownloadPdf-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Sound</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Sound</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnSound-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Fullscreen</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Fullscreen</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnExpand-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Select tool</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Select tool</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnSelect-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Search</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Search</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnSearch-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Bookmark</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Bookmark</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-btnBookmark-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>

                     <h3>Social share buttons</h3>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Google plus</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Google plus</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-google_plus-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Twitter</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Twitter</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-twitter-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Facebook</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Facebook</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-facebook-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share on Pinterest</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share on Pinterest</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-pinterest-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                     <div class="postbox closed">
                        <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Share by Email</span><span class="toggle-indicator" aria-hidden="true"></span></button>
                        <h2 class="hndle ui-sortable-handle"><span>Share by Email</span></h2>
                        <div class="inside">
                           <table class="form-table" id="flipbook-email-options">
                              <tbody></tbody>
                           </table>
                           <div class="clear"></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <p id="r3d-save" class="submit">
            <span class="spinner"></span>
            <input type="submit" name="btbsubmit" id="btbsubmit" class="alignright button save-button button-primary" value="Update" style="display:none;">
            <input type="submit" name="btbsubmit" id="btbsubmit" class="alignright button create-button button-primary" value="Publish" style="display:none;">
            <a id="r3d-preview" href="#" class="alignright flipbook-preview button save-button button-secondary">Preview</a>
            <a href="#" class="alignright flipbook-reset-defaults button button-secondary">Reset all settings</a>
         </p>
         <div id="r3d-save-holder" style="display: none;" ></div>
      </form>
   </div>
</div>
<?php 
wp_enqueue_media();
add_thickbox(); 

wp_enqueue_script( "real3d-flipbook-iscroll" ); 
wp_enqueue_script( "real3d-flipbook-pdfjs" ); 
wp_enqueue_script( "real3d-flipbook-pdfworkerjs" ); 
wp_enqueue_script( "real3d-flipbook-pdfservice" ); 
wp_enqueue_script( "real3d-flipbook-threejs" ); 
wp_enqueue_script( "real3d-flipbook-book3" );
wp_enqueue_script( "real3d-flipbook-bookswipe" );
wp_enqueue_script( "real3d-flipbook-webgl" );
wp_enqueue_script( "real3d_flipbook" );
wp_enqueue_style( 'real3d-flipbook-style' ); 
wp_enqueue_style( 'real3d-flipbook-font-awesome' ); 

wp_enqueue_script( 'alpha-color-picker' );
wp_enqueue_script( 'real3d-flipbook-admin' ); 
wp_enqueue_style( 'alpha-color-picker' );
wp_enqueue_style( 'real3d-flipbook-admin-css' ); 

if (class_exists('R3D_PDF_Addon')) {
   $r3d_pdf_addon = R3D_PDF_Addon::get_instance();
   wp_enqueue_script("r3d-pdf-addon", $r3d_pdf_addon->PLUGIN_DIR_URL."js/pdf-addon.js", array('jquery'),$r3d_pdf_addon->PLUGIN_VERSION);
}

$ajax_nonce = wp_create_nonce( "saving-real3d-flipbook");
$flipbooks[$current_id]['security'] = $ajax_nonce;
$flipbook_global = get_option("real3dflipbook_global");

$flipbook_global_defaults = r3dfb_getDefaults();

$flipbooks[$current_id]['globals'] = array_merge($flipbook_global_defaults, $flipbook_global);
wp_localize_script( 'real3d-flipbook-admin', 'flipbook', json_encode($flipbooks[$current_id]) );
<div class="q-content q-add-form">
    <form action="" method="post" id="question-form">
        <?php if(isset($id) && $id){ ?>
            <input type="hidden" name="id" value="<?php echo $id;?>">
        <?php }
        wp_nonce_field( 'QAPress_add_question', 'add_question_nonce' ); ?>
        <div class="q-add-header clearfix">
            <div class="q-add-title">
                <div class="q-add-label"><?php _e('Title: ', 'wpcom');?></div>
                <div class="q-add-input"><input type="text" name="title" placeholder="<?php _e('Please enter your title', 'wpcom');?>" value="<?php echo $title;?>"></div>
            </div>
            <div class="q-add-cat">
                <div class="q-add-label"><?php _e('Category: ', 'wpcom');?></div>
                <div class="q-add-input">
                    <select name="category" id="category">
                        <option value=""><?php _e('-- Select --', 'wpcom');?></option>
                        <?php if($qa_cats){ foreach ($qa_cats as $cat) { ?>
                            <option value="<?php echo $cat->term_id;?>" <?php echo $category==$cat->term_id?'selected':'';?>><?php echo $cat->name;?></option>
                        <?php } } ?>
                    </select>
                </div>
            </div>
            <div class="q-add-btn"><input class="btn btn-post" type="submit" value="<?php _ex('Submit', 'qapress', 'wpcom');?>"></div>
        </div>
        <div class="q-add-main"><?php echo $editor_contents;?></div>
    </form>
</div>
<?php if(!class_exists('raintpl')){exit;}?><div class="detail-image row">
    <div class="col-lg-8"><img src="<?php echo $image;?>" /></div>
    <div class="col-lg-4">
        <h2><?php echo $title;?></h2>
        <dl>
        <?php $counter1=-1; if( isset($meta) && is_array($meta) && sizeof($meta) ) foreach( $meta as $key1 => $value1 ){ $counter1++; ?>

            <dt><?php echo $value1["title"];?></dt>
            <dd><?php echo $value1["value"];?></dd>
        <?php } ?>

        </dl>
        <a style="margin-top: 20px" href="<?php echo $buy_link;?>" class="btn btn-discreet"><?php echo $buy_text;?></a>
    </div>
</div>
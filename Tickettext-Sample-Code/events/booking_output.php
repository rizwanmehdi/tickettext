<?php 
error_reporting(E_ALL);

ini_set('display_errors', true);

// File Name:   booking_output.php
// File Reason: Performers list in event page in frontend output below.

// Show only if performers are passed to this file.
if($performers):
?>
<div class="event-booking-performers">
    <h3>Featuring Guest<?php if(count($performers) > 1) echo 's'; ?>:</h3>
    <div class="performers-list">
        <ul>
            <?php foreach($performers as $performer):?>
                <li>
                    <span class="performer-name <?php if($performer->description):?>with-desc-info<?php else:?>no-desc-info<?php endif;?>">
                        <span class="name"><?php echo $performer->name; ?></span>
                        <?php if($performer->description):?><div class="performer-desc"><?php echo $performer->description; ?></div><?php endif;?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>    
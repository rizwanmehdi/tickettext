<div class="overlay">
    <div class="header">
        <h3>Thanks for purchasing...</h3>
        <h2><?php echo $ticket->quantity; ?> ticket<?php if($ticket->quantity > 1) echo 's'; ?> to the <?php echo strtoupper($ticket->event_name); ?></h2>
        <div class="close">X</div>
    </div>
    <div class="body">
        <p>For more great offers on upcoming events</p>
        <p>follow us on Facebook and Twitter...</p>
        <ul class="social-links">
            <li class="fb"><a href="https://www.facebook.com/tkttxt" target="_blank"><span class="fa fa-facebook"></span> Facebook</a></li>
            <li class="twitter"><a href="https://twitter.com/tkttxt" target="_blank"><span class="fa fa-twitter"></span> Twitter</a></li>
        </ul>
        <p>Close this window to see your order</p>
    </div>
</div>

<script type="text/javascript">
<!--
jQuery(document).ready(function($){
    var overlay = $('.block-overlay');

    overlay.find('.close').click(function(e){
        overlay.fadeOut(500);
    });
});
//-->
</script>
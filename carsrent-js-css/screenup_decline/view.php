<div class="screenup-wrap screenup-decline">
    <div class="screenup screenup-400">
        <div class="screenup-top">
            <a href="#" class="screenup-close screenup-decline-close"><i class="fa fa-times-circle"></i></a>
            <div class="screenup-ttl mb10"><?=translate('NOTPAYINGNOWCAUSE','screenup_decline')?>:</div>
        </div>
        <form class="screenup-decline-form">

            <ul class="screenup-decline-reason-radiolist">
                <? foreach ($declineReasons as $dr)
                   { ?>
                       <li>
                           <input type="radio" id="screenup-decline-<?=$dr->id ?>" value="<?=$dr->id ?>" name="declinereason">
                           <label for="screenup-decline-<?=$dr->id ?>">
                               <div class="check"></div>
                               <?=getPropertyLocalized($dr, 'name', $currlang) ?>
                           </label>
                           <? if ($dr->id==66003): ?>
                               <div class="screenup-decline-customprice">
                                   <span><?=translate('SPECIFY_CUSTOM_PRICE','screenup_decline')?></span>
                                   <input type="text" name="customprice">
                               </div>
                           <? endif; ?>
                       </li>

                <? } ?>
            </ul>
            <a href="#" class="screenup-decline-comment-toggle"><i class="fa fa-comments"></i><?=translate('LEAVECOMMENT','screenup_decline')?></a>
            <textarea class="screenup-decline-comment screenup-decline-comment-hide"></textarea>
            <button class="screenup-btn screenup-decline-submitbtn" type="button"><?=translate('SEND','comtransfer') ?></button>
        </form>

    </div>
</div>

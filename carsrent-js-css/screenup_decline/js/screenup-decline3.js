var screenupDecline={
    idleTime : 0,
    allowShow : false,
    selector : '.screenup-decline',
    cookieName : 'screenup-decline',
    sessiondurationMinutes : 30,
    init : function($doc)
    {
        console.log('init');
        var self=this;
        this.idleInterval = setInterval( self.timerIncrement, 60000);
        setTimeout(function(){ self.allowShow=true; }, 15000);

        $(this.selector + '-close').click(function(e)
        {
            e.preventDefault();
            self.hidePopup();
        });

        $(this.selector + ' input[name="declinereason"]').on('click change', function(e) {
           if (this.value=='66003')
           {
               $(self.selector + '-customprice').show();
           }
            else
           {
               $(self.selector + '-customprice').hide();
           }
        });

        $(this.selector + '-comment-toggle').click(function(e)
        {
            e.preventDefault();
            $(self.selector + '-comment').toggleClass('screenup-decline-comment-hide');
        });

        $(this.selector + '-submitbtn').click(function(e)
        {
            var $form=$(self.selector + '-form' );
            var $radioSelected = $form.find('input[name="declinereason"]:checked');
            var $transferDiv = $('.transfer');
            var commentText = $form.find(self.selector + '-comment').val();
            var customPrice=$form.find('input[name="customprice"]').val();

            if (($radioSelected.length==0) && !(commentText))
            {
                swal({
                    title: translate_screenup_decline.ERROR,
                    text: translate_screenup_decline.SELECTREASON,
                    type: "warning",
                    confirmButtonColor: "#ffac00"
                });
                return;
            }

            var submitParams={
                f:'json',
                a:'submitDecline',
                declineReason:$radioSelected.val()
            };

            if (commentText)
            {
                submitParams.comment=commentText;
            }

            if (customPrice)
            {
                submitParams['customPrice']=customPrice;
            }


            if ($transferDiv.length>0)
            {
                submitParams.placeFrom = $transferDiv.data('placefrom');
                submitParams.placeTo = $transferDiv.data('placeto');
                submitParams.declinePage = 66102;
            }
            else
            {
                submitParams.declinePage = 66101;
            }

            console.log('submitting',submitParams);

            $.getJSON('/gate.php',submitParams,function()
            {
                swal({
                    title: translate_screenup_decline.GRATS,
                    text: translate_screenup_decline.YOURREASONSENT,
                    type: "warning",
                    confirmButtonColor: "#ffac00"
                });
                self.hidePopup();
            });
        });


        $(this.selector + ' ' + '.screenup-close').click(function(e)
        {
            self.hidePopup();
        });

        $doc.mousemove(function (e) {
            self.idleTime = 0;
        });

        $doc.keypress(function (e) {
            self.idleTime = 0;
        });
        $doc.mouseleave(function(e){
            if (self.allowShow)
            {
                self.showPopup();
            }
        });

        $(this.selector + '-comment').on('change keydown drop paste cut', function() {

            var $that = $(this);

            // размер будет изменяться по истечении 1 милисекунды
            window.setTimeout(function() {

                // сбросим высоту скрола (нельзя ставить auto, т.к. при "свёрнутом" состоянии - минимальный размер - увеличивается на 10 пикселей)
                $that.attr('style', 'height: 0px');

                // получим значение "высота скрола"
                var scrollHeight = $that.prop('scrollHeight');

                // установим "новую высоту скролла"
                $that.attr('style', 'height:' + (scrollHeight + 5) + 'px;');
            }, 1);
        });

    },
    timerIncrement : function() {
        this.idleTime = this.idleTime + 1;
        if (this.idleTime > 30) {
            this.showPopup()
        }
    },

    showPopup : function()
    {
        if ($.cookie(this.cookieName))
        {
            return;
        }
        var d = new Date();
        d.setTime(d.getTime() + (60*this.sessiondurationMinutes*1000));
        $.cookie(this.cookieName, "1", { expires: d, path: '/' });

        $(this.selector).show();
    },
    hidePopup : function()
    {
        $(this.selector).hide();
    },
};

$(document).ready(function () {
    screenupDecline.init($(document));
});
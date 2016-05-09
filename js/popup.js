jQuery.fn.extend({
    popup: function(element) {
        return this.each(function() {
            $(this).click(function() {
                $(element).css("display", "block");
            });
        });
    }
});

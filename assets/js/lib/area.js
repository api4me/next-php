define(['zepto', 'text!/api/area/'], function($, area) {
    var data = JSON.parse(area);
    return {
        create: function(se, province, city, district) {
            $('<select name="province" class="input-medium"></select>').appendTo($(se));
            $('<select name="city" class="input-medium"></select>').appendTo($(se));
            $('<select name="district" class="input-medium"></select>').appendTo($(se));

            $.each(data, function(k, v){
                var selected = (k == province)? " selected" : "";
                $("select[name='province']", $(se)).append($('<option value="'+k+'"'+selected+'>' + v["00"]["00"]  + '</option>'));
            });
            $("select[name='province']", $(se)).change(function(){
                $("select[name='city']", $(se)).empty();
                $.each(data[$(this).val()], function(k, v){
                    if (k != "00") {
                        var selected = (k == city)? " selected" : "";
                        $("select[name='city']", $(se)).append($('<option value="'+k+'"'+selected+'>' + v["00"]  + '</option>'));
                    }
                });
                $("select[name='city']", $(se)).trigger("change");
            }).trigger("change");
            $("select[name='city']", $(se)).change(function(){
                var province = $("select[name='province']", $(se)).val();
                $("select[name='district']", $(se)).empty();
                $.each(data[province][$(this).val()], function(k, v){
                    if (k != "00") {
                        var selected = (k == district)? " selected" : "";
                        $("select[name='district']", $(se)).append($('<option value="'+k+'"'+selected+'>' + v  + '</option>'));
                    }
                });
            }).trigger("change");
        }
    };
});

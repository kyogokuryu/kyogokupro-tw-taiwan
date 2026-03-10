window.addEventListener('DOMContentLoaded', function(){

    $(function(){

        for(var i=1; i<=4; i++){
            var pic = 'pic' + i;// + "_image";
            $('#' + pic + "_image").on("change",function(e){
                var pic = "pic" + $(this).data('idx')
                //ファイルオブジェクトを取得する
                var file = e.target.files[0];
                var reader = new FileReader();
            
                //画像でない場合は処理終了
                if(file.type.indexOf("image") < 0){
                alert("画像ファイルを指定してください。");
                return false;
                }
            
                //アップロードした画像を設定する
                reader.onload = (function(file){
                return function(e){
                    $("#" + pic + "_preview").attr("src", e.target.result);

                    $("#" + pic + "_preview_div").show();
                    $('#' + pic + "_label").hide()
                    $('#' + pic + "_btn").show()
                };
                })(file);
                reader.readAsDataURL(file);
            
            });

            $('#' + pic + '_btn').on('click',function(){
                var pic = "pic" + $(this).data('idx')
                console.log(pic)
                $('#' + pic + '_label').show();
                $('#' + pic).val("");
                $('#' + pic + '_preview_div').hide();
                $('#' + pic + '_preview').attr("src", "");
                $('#' + pic + '_btn').hide()
                $('#' + pic).val("")
            })

            if($('#' + pic).val()){
                var img = $('#' + pic).val()
                $("#" + pic + "_preview").attr("src", img);

                $("#" + pic + "_preview_div").show();
                $('#' + pic + '_label').hide()
                $('#' + pic + '_btn').show()
            }
        }


        $('.thank-btn').on('click',function(){
            $(this).hide()
            $(this).parent().find('.icon-thank-msg').show()
        })


            $('.review-ref-button').on('click',function(){
                var id = $(this).data('rid');
                if(id){

                    $.ajax({
                        type: "GET",
                        url: "/review_list/addref?rid=" + id,
                        success: function(msg){
                            console.log( msg );
                        }
                    });

                    $(this).addClass('review-ref-pushed')
                        .prop('disabled',true)
                        ;
                    
                    var count = $('#ref_count' + id).data('ref');
                    $('#ref_count' + id).text( "" + (count + 1) )
                    $('.ref_msg' + id).show()
                }
            })

    });


})
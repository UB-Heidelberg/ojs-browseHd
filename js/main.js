$(document).ready(function(){
   //Das Div der Beschreibungstexte wird versteckt
   $('.browseHd_section_description').hide();

   $('.browseHd_category_description').hide();
});

$('.browseHd_section_extend > i').click(function(){
    $(this).parent().parent().find('.browseHd_section_description').slideToggle('500');
    $(this).parent().find('i').toggleClass('fa-minus-circle fa-plus-circle');
});

$('.browseHd_category_extend > i').click(function(){
    $(this).parent().parent().find('.browseHd_category_description').slideToggle('500');
    $(this).parent().find('i').toggleClass('fa-minus-circle fa-plus-circle');
});



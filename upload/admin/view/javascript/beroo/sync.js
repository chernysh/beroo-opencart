function setCurrentOffset(currentOffset){
	document.getElementById('currentOffset').innerHTML = currentOffset;
}
function setVariationCount(variationCount){
	document.getElementById('variationCount').innerHTML = variationCount;
}
function setTimeElapsed(timeElapsed){
	document.getElementById('timeElapsed').innerHTML = timeElapsed;
}

var $ = jQuery.noConflict();
$(document).ready(function ($) {
    var fcaImportButton = $('#fca-import-order');
    var progressBox = $('.fca_preloader');
    var resultDiv = $('.fca_ajax_result');
	var finishBlock = $('#sd-finish');

    if (fcaImportButton.length) {
        //console.log(DataObject);
        fcaImportButton.on('click', function () {
            resultDiv.slideUp(100);
            progressBox.slideDown(250);
			fcaImportButton.slideUp(0);
            getProducts();
        });
    }

    function getProducts() {
		currentOffset = document.getElementById('currentOffset').innerHTML;
		variationCount = document.getElementById('variationCount').innerHTML;
		timeElapsed = document.getElementById('timeElapsed').innerHTML;
		importProductsUrl = document.getElementById('importProductsUrl').innerHTML;
		importProductsUrl = importProductsUrl.replace(/\&amp\;/g, '&');
		console.log(importProductsUrl);
		$.ajax({
            type: 'POST',
            url: importProductsUrl,
            data: {
				offset: currentOffset,
				variationCount: variationCount,
				timeElapsed: timeElapsed
            },

            success: function (response) {
				response = JSON.parse(response);
				resultDiv.slideDown(100);
				setCurrentOffset(response['exported']);
				setVariationCount(response['variationCount']);
				setTimeElapsed(response['timeElapsed']);
				if(response['finish']!=1){
					getProducts();
				}
				else{
					progressBox.slideUp(250);
					finishBlock.slideDown(100);
				}
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        });
    }
});
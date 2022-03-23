$(document).ready(function(){
	document.body.classList.remove('loading');

	/* ------------------------------------------------------ */

	// Settings

	const SLIDE_TIME = 200;

	/* ------------------------------------------------------ */

	// Main menu

	const menu = $('#main-menu');
	const drawerCloser = menu.find('.drawer-closer');
	const drawer = menu.find('.drawer');

	$('#mobile-header > .menu').click(function () {
		menu.css('backgroundColor', 'rgba(255, 255, 255, 0.8)');
		drawerCloser.css('right', '0');
		drawer.css('left', '0');
	});

	drawerCloser.click(function () {
		menu.css('backgroundColor', 'rgba(255, 255, 255, 0.0)');
		drawerCloser.css('right', '-100%');
		drawer.css('left', '-80%');
	});

	/* ------------------------------------------------------ */

	// Cards

	window.toggleCard = function (event, element) {
		if (event.target === element) {
			const card = $(element);
			const expanderIcon = card.find('.header > i, .details > i');
			const content = card.find('.content');

			if (content.length > 0 && expanderIcon.length > 0) {
				content.slideToggle(SLIDE_TIME);

				if (expanderIcon.html() === 'expand_more') {
					expanderIcon.html('expand_less');
				} else {
					expanderIcon.html('expand_more');
				}
			}
		}
	}

	$('.card:not(.appointment, .person)').click(function (event) {
		window.toggleCard(event, this);
	});

    $('.card.category').each(function(index, el){
        if($(el).find('input:checked').length > 0) {
            $(el).find('.content').slideToggle(SLIDE_TIME);
        }
    });

	/* ------------------------------------------------------ */

	// Question categories

	const category = $('#category');
	const subCategory = $('#sub-category');
	let catSelected = false;

	$('#categorySelection input[type=radio]').click(function () {
		category.html($(this).closest('.category').find('.title').html() + " : ");
		subCategory.html($(this).next('label').html());

		if (catSelected === false) {
			category.css({ display: 'inline' });
			catSelected = true;
			enableNext();
		}
	});

	/* ------------------------------------------------------ */

	// Question appointments

	$('.card.appointment').click(function (event) {
		if (event.target === this) {
			const card = $(this);
			const inputField = card.siblings('input[type=radio]');
			const content = card.find('.content');

			if (inputField.prop('disabled') === false) {
				const appointmentList = $('.card.appointment');

				content.slideDown(SLIDE_TIME);

				for (const temp of appointmentList) {
					if (temp !== this) {
						$(temp).find('.content').slideUp(SLIDE_TIME);
					} else if(inputField.val() !== 'inloop') {
                        $(temp).find('input[name="availablePersonGroup[]"]').each(function( index ) {
                            if($(this).is(':checked')) {
                                enableNext();
                            }
                        });
                    } else {
                        enableNext();
                    }
				}
			}
		}
	});

    $('.card.appointment li.checkbox input').click(function (event) {
       let isEnableNext = false;
       $(this).closest('.details-with-options').find('input[name="availablePersonGroup[]"]').each(function(index) {
           if($(this).is(':checked')) {
               isEnableNext = true;
           }
       });
       isEnableNext ? enableNext() : disableNext();
    });

    $('input[name="AppointmentType"]:checked').next('.card.appointment').trigger('click');


	/* ------------------------------------------------------ */

	/** Blocked Timeslot Add / Remove **/

	function removeTimeslotListener(){
		$(".remove-slot").click(function(){
			event.preventDefault();
			this.closest('li').remove();
		});
	}

	removeTimeslotListener();


	//@jan we need to turn this into twig somehow

	$("#addTimeslot").click(function(event){
		event.preventDefault();

		$(".timeslot-list").append('\
        	<li class="time-range">\
				<div class="input-group time-select">\
					<div class="select-group">\
						<div class="select-wrapper">\
							<select>\
								<option disabled>HH</option>\
								<option>01</option>\
								<option>02</option>\
								<option>03</option>\
								<option>04</option>\
								<option>05</option>\
							</select>\
						</div>\
						<div class="select-wrapper">\
							<select>\
								<option disabled>MM</option>\
								<option>00</option>\
								<option>15</option>\
								<option>30</option>\
								<option>45</option>\
								<option>60</option>\
							</select>\
						</div>\
					</div>\
				</div>\
				<span>-</span>\
				<div class="input-group time-select">\
					<div class="select-group">\
						<div class="select-wrapper">\
							<select>\
								<option disabled>HH</option>\
								<option>01</option>\
								<option>02</option>\
								<option>03</option>\
								<option>04</option>\
								<option>05</option>\
							</select>\
						</div>\
						<div class="select-wrapper">\
							<select>\
								<option disabled>MM</option>\
								<option>00</option>\
								<option>15</option>\
								<option>30</option>\
								<option>45</option>\
								<option>60</option>\
							</select>\
						</div>\
					</div>\
				</div>\
				<a href="javascript:void(0);" class="icon-button remove-slot">cancel</a>\
			</li>\
        ');


		removeTimeslotListener();
		itemID++;
	});

	/** New Questions **/

	function enableNext(){
		$('#next').removeClass('disabled');
	}

    function disableNext(){
        $('#next').addClass('disabled');
    }

	/** Question description */
	$('#userDescription').on('input', function(){
		let length = this.value.length
		if (length <= 5 ){
			$('#errormessage').css("display", "inline").html("Beschrijf je vraag uitgebreider");
			$('#next').addClass('disabled');
		} else{
			$('#next').removeClass('disabled');
			$('#errormessage').css("display", "none");
		}
	})

	/** Enable next when a time slot is selected **/
	$('#timeSelection input[type=radio]').click(enableNext);



	/** checks if something is selected on a page and enable next if it is **/
	if($(".do-check").length) questionSelectionChecker();

	function questionSelectionChecker(){
		if (!$("input[type='radio']:checked").val()) {
			console.log("nothing is selected");
		}

		else {
			enableNext();
		}
	}
});

// This example requires the Places library. Include the libraries=places
// parameter when you first load the API. For example:
// <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">
function initMap() {
	const map = new google.maps.Map(document.getElementById("map"), {
		center: { lat: 50.064192, lng: -130.605469 },
		zoom: 3
	});
	const input = document.getElementById("pac-input");
	const autocomplete = new google.maps.places.Autocomplete(input);
	// Set initial restrict to the greater list of countries.
	autocomplete.setComponentRestrictions({
		country: ["nl"]
	});
	// Specify only the data fields that are needed.
	autocomplete.setFields(["address_components", "geometry", "icon", "name"]);
	const marker = new google.maps.Marker({
		map,
		anchorPoint: new google.maps.Point(0, -29)
	});
	autocomplete.addListener("place_changed", () => {
		marker.setVisible(false);
		const place = autocomplete.getPlace();

		if (!place.geometry) {
			// User entered the name of a Place that was not suggested and
			// pressed the Enter key, or the Place Details request failed.
			window.alert("No details available for input: '" + place.name + "'");
			return;
		}

		// If the place has a geometry, then present it on a map.
		if (place.geometry.viewport) {
			map.fitBounds(place.geometry.viewport);
		} else {
			map.setCenter(place.geometry.location);
			map.setZoom(17); // Why 17? Because it looks good.
		}
		marker.setPosition(place.geometry.location);
		marker.setVisible(true);
		let address = "";

		if (place.address_components) {
			address = [
				(place.address_components[0] &&
					place.address_components[0].short_name) ||
				"",
				(place.address_components[1] &&
					place.address_components[1].short_name) ||
				"",
				(place.address_components[2] &&
					place.address_components[2].short_name) ||
				""
			].join(" ");
		}
	});
}

function PopUpHide(){
	$("#tutorial-popup").hide();
}

function ClientTutorialStart(userGuidingClientTutorialId){
	userGuiding.previewGuide(userGuidingClientTutorialId);
	PopUpHide();
}

function TutorialDone(url) {
	$.ajax({
		type: 'POST',
		url: url,
		data: {
			action: 'tutorialDone',
		},
		success: function (responce){
			if(responce.needPincode) {
				window.location.href = '/clientPortal/pincode.php?view=pincodeRequest'
			}
		}
	});
}

function SkipTutorial(url) {
	$.ajax({
		type: 'POST',
		url: url,
		data: {
			action: 'skipTutorial',
		},
		success: function (responce){
			let TutorialResponseAccept = JSON.parse(responce);
			if (TutorialResponseAccept.success){
				if(!$("#tutorial-popup").hide()){
					PopUpHide();
				}
			} else if(responce.needPincode) {
				window.location.href = '/clientPortal/pincode.php?view=pincodeRequest'
			}
		}
	});
}

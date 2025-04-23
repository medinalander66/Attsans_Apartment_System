import {  initLoginForm, initSignupForm, handleSignupSuccessMessage } from './auth.js';
import { initMenuUpdate } from './menu.js';
import { checkUserSession, preloadUserInfo, submitInquiryForm } from './session.js';
import { loadRoomDetails, loadRandomRooms, loadInquireRoomDetails } from './property.js';

document.addEventListener('DOMContentLoaded', function () {
    const page = document.body.dataset.page;

    switch (page) {
        case 'login-page':
            initLoginForm();
            handleSignupSuccessMessage();
            break;

        case 'signup-page':
            initSignupForm();
            break;

        case 'home-page':
            initMenuUpdate();
            break;
        
        case 'how-it-works-page':
            initMenuUpdate();
            break;
        
        case 'privacy-policy-page':
            initMenuUpdate();
            break;
        
        case 'customer-support-page':
            initMenuUpdate();
            break;

        case 'properties-section-page':
            initMenuUpdate();
            break;

        case 'property-page':
            initMenuUpdate();
            loadRoomDetails();
            loadRandomRooms();
            break;

        case 'inquire-logged-in-page':
            initMenuUpdate();
            checkUserSession();
            loadInquireRoomDetails();
            handleIDUpload('submit-id-upload', 'gov_id', 'input[name="move_in_date"]', 'agree', 'error_message', 'backend.php');
            submitInquiryForm('inquiry-form', 'error_message', 'backend.php');
            break;


    }
});

<?php

/*
|--------------------------------------------------------------------------
| Error codes — English
|--------------------------------------------------------------------------
|
| One entry per code: what the person saw, why it happened, and what to
| do about it. The codes themselves, their families and who can act on
| them live in config/error_codes.php.
|
| These words are read by people who are already stuck, so they say what
| happened in plain terms and then what to do. They do not blame the
| reader, and they never end without a next step.
|
*/

return [

    'PM-9901' => [
        'title' => 'Page not found',
        'what' => 'The address asked for does not exist in Patrimoine. Usually a link is out of date, an address was mistyped, or the record it pointed at has since been deleted.',
        'fix' => 'Use the menu to get where you were going. If you followed a link from inside Patrimoine and it led here, tell us which one — a link that goes nowhere is our mistake, not yours.',
    ],

    'PM-9902' => [
        'title' => 'Your session has ended',
        'what' => 'Patrimoine signs you out after a period without activity, so an unattended screen cannot be used by somebody else. The page was still open, but the session behind it had already closed.',
        'fix' => 'Sign in again. Anything you had saved is safe; anything typed into a form but not saved will need to be entered again.',
    ],

    'PM-9903' => [
        'title' => 'Too many attempts',
        'what' => 'Patrimoine limits how often the same action can be repeated in a short time. This protects accounts from being guessed at and keeps the service responsive for everyone.',
        'fix' => 'Wait a minute, then try once more. If you were signing in and cannot remember the password, use "Forgotten password" instead of guessing again.',
    ],

    'PM-9904' => [
        'title' => 'Something went wrong on our side',
        'what' => 'Patrimoine hit an error it did not expect while handling the request. Nothing you were saving was lost — an action that fails this way is not half-recorded, it simply does not happen.',
        'fix' => 'Try once more, in case it was momentary. If it happens again, contact us with this code and what you were doing. Every one of these is recorded on our side with enough detail to find it.',
    ],

    'PM-9905' => [
        'title' => 'Patrimoine is briefly unavailable',
        'what' => 'The service is being updated or restarted. This is normally a matter of seconds, and no data is at risk while it happens.',
        'fix' => 'Wait a moment and reload the page. If it lasts more than a few minutes, contact us — by then it is no longer a routine update.',
    ],

    'PM-9906' => [
        'title' => 'Patrimoine could not be reached',
        'what' => 'The browser could not reach the service at all. Either this device is offline, or something between it and Patrimoine — a network, a firewall, a mobile connection — is in the way.',
        'fix' => 'Check that other websites load. If they do and Patrimoine still does not, tell us: at that point the problem is ours to find.',
    ],

    /* ---- 1xxx ---- */

    'PM-1001' => [
        'title' => 'This account has been disabled.',
        'what' => 'This account has been switched off by an administrator in your organisation. It still exists, with all its history — it simply cannot sign in.',
        'fix' => 'Ask an administrator to activate it again on the Users page. If nobody remembers switching it off, the activity log records who did and when.',
    ],

    'PM-1002' => [
        'title' => 'You are not authorized to perform this action.',
        'what' => 'Your role does not include this action. Patrimoine has three roles, and each sees a different amount: an administrator manages everything, a manager runs the day-to-day, and a viewer reads without changing.',
        'fix' => 'Ask an administrator in your organisation to change your role on the Users page, or to carry out this particular action for you.',
    ],

    'PM-1003' => [
        'title' => 'The provided credentials are incorrect.',
        'what' => 'The email address and password together do not match an active account. For safety Patrimoine does not say which of the two is wrong.',
        'fix' => 'Check the email address for typos and try the password again. If you cannot remember it, use "Forgotten password" on the sign-in page to set a new one. If the account was recently deactivated, an administrator in your organisation can switch it back on.',
    ],

    'PM-1004' => [
        'title' => 'This sign-in attempt has expired. Sign in again to receive a new code.',
        'what' => 'Sign-in codes are short-lived. Too much time passed between asking for the code and entering it, so it is no longer accepted.',
        'fix' => 'Sign in again with your email address and password. A fresh code is sent immediately, and it is the one to use.',
    ],

    'PM-1005' => [
        'title' => 'The verification code is incorrect.',
        'what' => 'The six digits entered do not match the code Patrimoine sent for this sign-in.',
        'fix' => 'Check the most recent email — an older code from a previous attempt will not work. Type the six digits without spaces. If no email has arrived, look in your junk folder, then ask for a new code.',
    ],

    'PM-1006' => [
        'title' => 'This organisation is currently suspended. Contact support@patrimoine365.com.',
        'what' => 'This organisation has been suspended, so nobody in it can sign in. Suspension is applied by Patrimoine, usually over an unpaid subscription or at the organisation’s own request.',
        'fix' => 'Your data is untouched and comes back exactly as it was once the suspension is lifted. Contact us to find out why it was applied and what is needed to restore access.',
    ],

    'PM-1007' => [
        'title' => 'The password is incorrect.',
        'what' => 'The password typed to confirm this action does not match the one you sign in with.',
        'fix' => 'Type your sign-in password again. If you have forgotten it, sign out and use "Forgotten password" to set a new one, then come back to this.',
    ],

    'PM-1008' => [
        'title' => 'Complete your account setup before signing in.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-1009' => [
        'title' => 'Unauthenticated.',
        'what' => 'Patrimoine did not recognise the session behind this request. Usually it had simply ended while the page stayed open.',
        'fix' => 'Sign in again. If you were part-way through a form, copy anything you had typed before you do — an ended session cannot be resumed.',
    ],

    'PM-1010' => [
        'title' => 'Verify your email address before signing in. Check your inbox for the verification link.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-1011' => [
        'title' => 'Unable to sign in.',
        'what' => 'The sign-in request did not come back. This is not about the password being wrong — Patrimoine never got far enough to check it.',
        'fix' => 'Check that other websites load, then try again. If your connection is fine and this keeps happening, tell us: sign-in failing is ours to fix, not yours.',
    ],

    'PM-1012' => [
        'title' => 'The password confirmation does not match.',
        'what' => 'The two boxes do not contain the same thing. Patrimoine asks twice precisely so a typo cannot lock you out later.',
        'fix' => 'Type it again in both boxes, slowly. If your browser filled one of them in for you, clear both first.',
    ],

    'PM-1013' => [
        'title' => 'The current password is incorrect.',
        'what' => 'Patrimoine asks for your own password before something irreversible, so that an unattended screen cannot be used to destroy records.',
        'fix' => 'Type the password you sign in with. If you have forgotten it, sign out and use "Forgotten password" to set a new one, then come back.',
    ],

    'PM-1014' => [
        'title' => 'This password reset link is invalid or has expired.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-1015' => [
        'title' => 'That password request did not go through.',
        'what' => 'A request did not come back. Usually the connection dropped or the session ended while the page was open.',
        'fix' => 'Try again, and reload the page if it fails a second time. If it keeps happening, tell us what you were doing.',
    ],

    'PM-1016' => [
        'title' => 'That file could not be read as an image. Use a JPG, PNG, WEBP or GIF.',
        'what' => 'The file could not be read as a picture. Either it is not an image, or it is in a format this browser cannot decode.',
        'fix' => 'Use a JPG, PNG or WEBP file. HEIC photos straight from an iPhone only work in Safari — on other browsers, export the photo as JPG first.',
    ],

    'PM-1017' => [
        'title' => 'Profile photo removed.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-1018' => [
        'title' => 'This email domain is reserved. Contact support@patrimoine365.com.',
        'what' => 'This action needs a level of access, or a plan, that the account does not currently have.',
        'fix' => 'Ask an administrator in your organisation. They can change roles on the Users page and see the plan and its limits on the Licence page.',
    ],

    'PM-1019' => [
        'title' => 'This verification link is invalid or has expired. Request a new one from the sign-in page.',
        'what' => 'Verification links can only be used once, and they expire. This one has already been used or has passed its expiry.',
        'fix' => 'Enter your email address on the verification page and ask for a new link. Open the newest email you receive — older links stay dead.',
    ],

    'PM-1020' => [
        'title' => 'Start your 30-day Professional trial. No payment card required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-1021' => [
        'title' => 'Unable to create your organisation.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1022' => [
        'title' => 'Your account setup is complete. You can now sign in.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-1023' => [
        'title' => 'An invitation cannot be sent to an inactive user.',
        'what' => 'The account is switched off, and Patrimoine does not invite somebody to an account they could not use.',
        'fix' => 'Activate the account first on the Users page. The invitation goes out by itself the moment it is activated.',
    ],

    'PM-1024' => [
        'title' => 'This invitation link is invalid or has expired.',
        'what' => 'This invitation link has expired, has already been accepted, or was replaced when a newer invitation was sent to the same person.',
        'fix' => 'Ask an administrator in your organisation to send the invitation again from the Users page, then open the newest email.',
    ],

    'PM-1025' => [
        'title' => 'An administrator’s own role has to be changed by another administrator.',
        'what' => 'An administrator cannot lower their own role, because doing so could leave the organisation with nobody able to restore it.',
        'fix' => 'Ask another administrator to change your role for you.',
    ],

    'PM-1026' => [
        'title' => 'An account cannot delete itself. Another administrator can do it.',
        'what' => 'You cannot delete the account you are signed in with.',
        'fix' => 'Ask another administrator to delete it once you have signed in as somebody else.',
    ],

    'PM-1027' => [
        'title' => 'An account cannot switch itself off. Another administrator can do it.',
        'what' => 'You are signed in as this user, and switching off your own account would lock you out mid-action.',
        'fix' => 'Ask another administrator to do it, or sign in as a different administrator first.',
    ],

    'PM-1028' => [
        'title' => 'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
        'what' => 'Every organisation must keep at least one active administrator, otherwise nobody could manage users, settings or licences ever again.',
        'fix' => 'Make somebody else an administrator first, then repeat what you were doing.',
    ],

    'PM-1029' => [
        'title' => 'Platform staff accounts must use an @patrimoine365.com email address.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-1030' => [
        'title' => 'This email domain is reserved for platform staff.',
        'what' => 'This action needs a level of access, or a plan, that the account does not currently have.',
        'fix' => 'Ask an administrator in your organisation. They can change roles on the Users page and see the plan and its limits on the Licence page.',
    ],

    'PM-1031' => [
        'title' => 'That change to the user was not saved.',
        'what' => 'A request did not come back. Usually the connection dropped or the session ended while the page was open.',
        'fix' => 'Try again, and reload the page if it fails a second time. If it keeps happening, tell us what you were doing.',
    ],

    'PM-1032' => [
        'title' => 'Unable to create user.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1033' => [
        'title' => 'Unable to delete user.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-1034' => [
        'title' => 'Unable to load users.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-1035' => [
        'title' => 'Unable to update user.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1036' => [
        'title' => 'Link invalid or expired',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-1037' => [
        'title' => 'Unable to send a new link right now.',
        'what' => 'The message could not be handed to the mail service. The document itself was created and is available.',
        'fix' => 'Check that the party has a valid email address, then send again. If the address is right and it still fails, download the document and send it yourself while we look into it.',
    ],

    'PM-1038' => [
        'title' => "The name you typed is not your organisation's name.",
        'what' => 'Closing an account destroys everything in it, so the name has to be typed back exactly before it will run. What was typed does not match.',
        'fix' => 'Copy the name exactly as Settings shows it, including capital letters, spaces and punctuation. If you did not mean to close the account, close this panel instead.',
    ],

    'PM-1039' => [
        'title' => 'This account cannot be closed from here.',
        'what' => 'You are signed in to the organisation that operates Patrimoine itself. Closing it would take the platform down with it, so the screen refuses.',
        'fix' => 'Nothing to put right. If an account really does need closing, do it from the administration console against the customer organisation concerned.',
    ],

    'PM-1040' => [
        'title' => 'That person has already been erased.',
        'what' => 'Erasure runs once. The record you are looking at carries a reference rather than a name because somebody has already asked for it, and there is nothing identifying left to remove.',
        'fix' => 'Nothing to do. If you expected to see a name here, you are looking at a record that was erased on request — the activity log will say when, and who authorised it.',
    ],

    'PM-1041' => [
        'title' => 'Your own organisation cannot be erased.',
        'what' => 'The party you chose is the managing organisation — your own company. Every invoice, receipt and statement names it as the producer, so erasing it would leave those documents unable to say who issued them.',
        'fix' => 'If your company details have changed, edit them instead. If you are closing the business, close the account from the foot of Settings rather than erasing the party.',
    ],

    'PM-1042' => [
        'title' => 'The name you typed is not the name on the record.',
        'what' => 'Erasing a person destroys their name, contact details and everything else identifying them, permanently, so the name has to be typed back exactly before it will run.',
        'fix' => 'Copy the name exactly as the record shows it, including capital letters, spaces and punctuation. If you did not mean to erase anybody, close this panel instead.',
    ],

    /* ---- 2xxx ---- */

    'PM-2001' => [
        'title' => 'The this field field must be accepted.',
        'what' => 'A box that has to be ticked was left unticked — normally the agreement to the terms.',
        'fix' => 'Tick the box to continue. If you would rather read the document first, the link beside it opens it in a new tab.',
    ],

    'PM-2002' => [
        'title' => 'The this field field must be accepted when :other is :value.',
        'what' => 'A box that has to be ticked was left unticked — normally the agreement to the terms.',
        'fix' => 'Tick the box to continue. If you would rather read the document first, the link beside it opens it in a new tab.',
    ],

    'PM-2003' => [
        'title' => 'The this field field must be a valid URL.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2004' => [
        'title' => 'The this field field must be a date after :date.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2005' => [
        'title' => 'The this field field must be a date after or equal to :date.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2006' => [
        'title' => 'The this field field must only contain letters.',
        'what' => 'This field accepts a limited set of characters, and something typed into it is outside that set.',
        'fix' => 'Remove punctuation, accents or symbols the field does not take. If a name genuinely contains them, use the closest plain spelling here and record the full version in the notes.',
    ],

    'PM-2007' => [
        'title' => 'The this field field must only contain letters, numbers, dashes, and underscores.',
        'what' => 'This field accepts a limited set of characters, and something typed into it is outside that set.',
        'fix' => 'Remove punctuation, accents or symbols the field does not take. If a name genuinely contains them, use the closest plain spelling here and record the full version in the notes.',
    ],

    'PM-2008' => [
        'title' => 'The this field field must only contain letters and numbers.',
        'what' => 'This field accepts a limited set of characters, and something typed into it is outside that set.',
        'fix' => 'Remove punctuation, accents or symbols the field does not take. If a name genuinely contains them, use the closest plain spelling here and record the full version in the notes.',
    ],

    'PM-2009' => [
        'title' => 'The this field field must be an array.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2010' => [
        'title' => 'The this field field must be a date before :date.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2011' => [
        'title' => 'The this field field must be a date before or equal to :date.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2012' => [
        'title' => 'The this field field must have between :min and :max items.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2013' => [
        'title' => 'The this field field must be between :min and :max kilobytes.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2014' => [
        'title' => 'The this field field must be between :min and :max.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2015' => [
        'title' => 'The this field field must be between :min and :max characters.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2016' => [
        'title' => 'The this field field must be true or false.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2017' => [
        'title' => 'The this field field confirmation does not match.',
        'what' => 'The two boxes do not contain the same thing. Patrimoine asks twice precisely so a typo cannot lock you out later.',
        'fix' => 'Type it again in both boxes, slowly. If your browser filled one of them in for you, clear both first.',
    ],

    'PM-2018' => [
        'title' => 'The this field field must be a valid date.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2019' => [
        'title' => 'The this field field must be a date equal to :date.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2020' => [
        'title' => 'The this field field must match the format :format.',
        'what' => 'The date was not understood in the form it was given.',
        'fix' => 'Use the date picker rather than typing, which guarantees the format. If you must type, follow the example shown beside the field.',
    ],

    'PM-2021' => [
        'title' => 'The this field field must have :decimal decimal places.',
        'what' => 'This field takes a number, and what was entered is not one Patrimoine can read.',
        'fix' => 'Enter digits only — no currency symbol, no letters. Amounts in Patrimoine are whole units of your currency, so leave out decimals.',
    ],

    'PM-2022' => [
        'title' => 'The this field field and :other must be different.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2023' => [
        'title' => 'The this field field must be :digits digits.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2024' => [
        'title' => 'The this field field must be between :min and :max digits.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2025' => [
        'title' => 'The this field field must be a valid email address.',
        'what' => 'The address does not look like an email address, so Patrimoine will not save it — an address it cannot use is worse than none.',
        'fix' => 'Check for a missing @, a stray space, or a typo in the domain. Leave the field empty if the person has no email; Patrimoine will simply not send them anything.',
    ],

    'PM-2026' => [
        'title' => 'The this field field must end with one of the following: :values.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-2027' => [
        'title' => 'The selected this field is invalid.',
        'what' => 'The record chosen no longer exists, or does not belong to your organisation. It may have been deleted while this page was open.',
        'fix' => 'Reload the page so the lists are current, then choose again.',
    ],

    'PM-2028' => [
        'title' => 'The this field field must be an integer.',
        'what' => 'This field takes a number, and what was entered is not one Patrimoine can read.',
        'fix' => 'Enter digits only — no currency symbol, no letters. Amounts in Patrimoine are whole units of your currency, so leave out decimals.',
    ],

    'PM-2029' => [
        'title' => 'The this field field must not have more than :max items.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-2030' => [
        'title' => 'The this field field must not be greater than :max kilobytes.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2031' => [
        'title' => 'The this field field must not be greater than :max.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2032' => [
        'title' => 'The this field field must not be greater than :max characters.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2033' => [
        'title' => 'The this field field must have at least :min items.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-2034' => [
        'title' => 'The this field field must be at least :min kilobytes.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2035' => [
        'title' => 'The this field field must be at least :min.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2036' => [
        'title' => 'The this field field must be at least :min characters.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2038' => [
        'title' => 'The this field field must be a number.',
        'what' => 'This field takes a number, and what was entered is not one Patrimoine can read.',
        'fix' => 'Enter digits only — no currency symbol, no letters. Amounts in Patrimoine are whole units of your currency, so leave out decimals.',
    ],

    'PM-2039' => [
        'title' => 'The this field field format is invalid.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2040' => [
        'title' => 'The this field field is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2041' => [
        'title' => 'The this field field is required when :other is :value.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2042' => [
        'title' => 'The this field field is required unless :other is in :values.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2043' => [
        'title' => 'The this field field is required when :values is present.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2044' => [
        'title' => 'The this field field is required when :values is not present.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2045' => [
        'title' => 'The this field field must match :other.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-2046' => [
        'title' => 'The this field field must contain :size items.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-2047' => [
        'title' => 'The this field field must be :size kilobytes.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2048' => [
        'title' => 'The this field field must be :size.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2049' => [
        'title' => 'The this field field must be :size characters.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2050' => [
        'title' => 'The this field field must be a string.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2051' => [
        'title' => 'The this field has already been taken.',
        'what' => 'Another record already holds this value, and Patrimoine keeps it unique so the two cannot be confused later.',
        'fix' => 'Search for the existing record first — usually it is the one you meant. If both genuinely exist, distinguish them, for instance by adding a middle name or a unit number.',
    ],

    'PM-2052' => [
        'title' => 'Advance received date cannot be before the Lease start date.',
        'what' => 'The value falls outside the range this field allows — a percentage over the whole, or a date before the lease itself begins.',
        'fix' => 'Bring the figure inside the range named in the message. A date can never sit before the lease starts, and a percentage of something cannot be more than all of it.',
    ],

    'PM-2053' => [
        'title' => 'Advance Payment must be greater than zero when Advance already received is selected.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-2054' => [
        'title' => 'An Agent is required when an agent commission is configured.',
        'what' => 'A commission has been entered, but no agent is on the lease. Commission is money paid to somebody, so Patrimoine needs to know to whom.',
        'fix' => 'Either choose the agent on the lease, or set the commission back to zero.',
    ],

    'PM-2055' => [
        'title' => 'Selected Party must have the agent role.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2056' => [
        'title' => 'A Building is required when a Unit is selected.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2057' => [
        'title' => 'Management fee value must be zero when management fee type is none.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2058' => [
        'title' => 'Percentage management fee cannot exceed 100%.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-2059' => [
        'title' => 'Termination notice date is required when Lease status is notice.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2060' => [
        'title' => 'Payments cannot be recorded against a draft Lease.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-2061' => [
        'title' => 'Next rent increment date is required when a rent increment is configured.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2062' => [
        'title' => 'Next rent increment date must be empty when no rent increment is configured.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2063' => [
        'title' => 'Rent increment value must be zero when no rent increment is configured.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2064' => [
        'title' => 'Percentage rent increment cannot exceed 100%.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-2065' => [
        'title' => 'Enter a rent increment value when a rent increment is configured.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2066' => [
        'title' => 'Rent Reserve cannot exceed the total Advance Payment.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-2067' => [
        'title' => 'Selected Party must have the tenant role.',
        'what' => 'The party chosen as tenant is not marked as a tenant, so Patrimoine will not put them on a lease.',
        'fix' => 'Open the party and tick Tenant among their roles, then try again. Creating the lease through the Assistant does this for you.',
    ],

    'PM-2068' => [
        'title' => 'This Unit already has an active Lease.',
        'what' => 'A unit can carry only one live lease at a time, and this one already has an active or notice-period lease on it.',
        'fix' => 'Terminate the existing lease first, or choose a vacant unit. If the previous tenant has already left, complete the termination so the unit is free.',
    ],

    'PM-2069' => [
        'title' => 'Selected Unit does not belong to the selected Building.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-2070' => [
        'title' => 'This property has no recorded owner yet, so the wizard needs at least one.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2071' => [
        'title' => 'The selected unit does not belong to the selected property.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-2072' => [
        'title' => 'Choose the country this telephone number belongs to.',
        'what' => 'A telephone number cannot be dialled without knowing which country it is in. The same digits mean different people in different countries.',
        'fix' => 'Open the country list beside the number and choose the country. You can type the country name or its code to find it quickly.',
    ],

    'PM-2073' => [
        'title' => 'This telephone number does not look right.',
        'what' => 'The digits and the chosen country do not make a number that could be dialled. Usually one or two digits are missing, or the country is not the one the number belongs to.',
        'fix' => 'Check the digits against the number as it is written down, and check the country beside them. Leave out the leading zero — it is only used inside the country.',
    ],

    /* ---- 3xxx ---- */

    'PM-3001' => [
        'title' => 'This Building cannot be deleted while it still contains Units. Delete only unreferenced Units first; Units with Lease or financial history must be retained.',
        'what' => 'The property still contains units, and deleting it would take them with it.',
        'fix' => 'Delete the units first, one by one, then delete the property. A unit that carries leases or financial history cannot be deleted either, which is usually the real reason a property will not go.',
    ],

    'PM-3002' => [
        'title' => 'This Building cannot be deleted because financial or historical records reference it. Keep the Building for historical accountability.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3003' => [
        'title' => 'This lease cannot be deleted safely.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3004' => [
        'title' => 'Type DELETE exactly to confirm Lease deletion.',
        'what' => 'The confirmation text does not match what was asked for. It is deliberately awkward: it is the last thing standing between a slip of the hand and permanent deletion.',
        'fix' => 'Type it exactly as shown, in the same capitals, with no extra spaces.',
    ],

    'PM-3005' => [
        'title' => 'Only an unused draft lease can be deleted. An active lease, or one under notice, is ended by terminating it — and a terminated lease is kept as history.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-3006' => [
        'title' => 'This draft Lease cannot be deleted because contractual or financial history references it. Keep the Lease record.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-3007' => [
        'title' => 'The configured Managing Organisation cannot be deleted. Change the Managing Organisation configuration instead.',
        'what' => 'This party is your own company — the one whose name appears on invoices, receipts and statements. Patrimoine cannot delete it while it holds that position.',
        'fix' => 'If your company details are wrong, edit them in Settings. To hand the position to a different party, set that party as the managing organisation first.',
    ],

    'PM-3008' => [
        'title' => 'This Party cannot be deleted because it is referenced by Lease, ownership, agency or financial history. Keep the Party so historical records remain understandable.',
        'what' => 'This party appears in leases, ownership, agency or financial history. Deleting it would leave those records pointing at nobody, so Patrimoine keeps it.',
        'fix' => 'Keep the party — that is what makes the old records readable. If they are no longer someone you deal with, remove their roles instead, or simply leave them; a party with no active lease costs nothing.',
    ],

    'PM-3009' => [
        'title' => 'This Unit cannot be deleted because Lease or financial history references it. Keep the Unit and terminate the Lease instead where applicable.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3010' => [
        'title' => 'Unable to record the deduction.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3011' => [
        'title' => 'This Lease cannot be deleted safely.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3012' => [
        'title' => 'You must type DELETE exactly.',
        'what' => 'The confirmation text does not match what was asked for. It is deliberately awkward: it is the last thing standing between a slip of the hand and permanent deletion.',
        'fix' => 'Type it exactly as shown, in the same capitals, with no extra spaces.',
    ],

    'PM-3013' => [
        'title' => 'Unable to calculate the Lease deletion impact.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3014' => [
        'title' => 'Your current password is required.',
        'what' => 'Patrimoine asks for your own password before something irreversible, so that an unattended screen cannot be used to destroy records.',
        'fix' => 'Type the password you sign in with. If you have forgotten it, sign out and use "Forgotten password" to set a new one, then come back.',
    ],

    'PM-3015' => [
        'title' => 'A deletion reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-3016' => [
        'title' => 'Unable to load financial history.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3017' => [
        'title' => 'Unable to open document.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3018' => [
        'title' => 'Unable to cancel the rent increment.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3019' => [
        'title' => 'Unable to schedule the rent increment.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3020' => [
        'title' => 'Unable to load rent increments.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3021' => [
        'title' => 'Rent Reserve cannot exceed Total Advance Payment.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-3022' => [
        'title' => 'Unable to cancel termination.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3023' => [
        'title' => 'Unable to complete termination.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3024' => [
        'title' => 'Unable to initiate Lease termination.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3025' => [
        'title' => 'Unable to open the Termination Notice.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3026' => [
        'title' => 'Notice Date, Termination Date and final rental treatment are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3027' => [
        'title' => 'Unable to load the termination settlement.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3028' => [
        'title' => 'Items that must be resolved',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3029' => [
        'title' => 'Unable to add Security Deposit deduction.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3030' => [
        'title' => 'Unable to apply Consumable Advance.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3031' => [
        'title' => 'Unable to apply Rent Reserve.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3032' => [
        'title' => 'Unable to create Lease.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3033' => [
        'title' => 'Unable to delete Lease.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3034' => [
        'title' => 'Unable to finalize Security Deposit.',
        'what' => 'The step did not finish, so nothing was changed by it.',
        'fix' => 'Reload the page and look at the record before trying again — that shows whether any part of it went through. If it fails a second time, tell us what you were doing.',
    ],

    'PM-3035' => [
        'title' => 'Unable to initialize Leases.',
        'what' => 'The step did not finish, so nothing was changed by it.',
        'fix' => 'Reload the page and look at the record before trying again — that shows whether any part of it went through. If it fails a second time, tell us what you were doing.',
    ],

    'PM-3036' => [
        'title' => 'Unable to load Leases.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3037' => [
        'title' => 'Unable to load Lease.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3038' => [
        'title' => 'Unable to load Security Deposit.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3039' => [
        'title' => 'Unable to load Tenant Funds.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3040' => [
        'title' => 'Unable to open Security Deposit voucher.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3041' => [
        'title' => 'Unable to update Lease.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3042' => [
        'title' => 'The voucher could not be opened because the browser blocked the new tab.',
        'what' => 'The document opens in a new tab, and this browser blocked it. Browsers block tabs a page opens by itself unless they are told otherwise.',
        'fix' => 'Look for a blocked-popup notice in the address bar and allow it for Patrimoine. The document itself was created and can also be downloaded from the record it belongs to.',
    ],

    'PM-3043' => [
        'title' => 'Only an unreferenced Party can be deleted. Parties used by leases, ownership, agency or financial history must be retained.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3044' => [
        'title' => 'Unable to create Party.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3045' => [
        'title' => 'Unable to delete Party.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3046' => [
        'title' => 'Unable to load parties.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3047' => [
        'title' => 'Unable to load Party.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3048' => [
        'title' => 'Unable to update Party.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3049' => [
        'title' => 'Legal name and contact person details are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3050' => [
        'title' => 'Name, phone and email are required for a person.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3051' => [
        'title' => 'Unable to add unit.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3052' => [
        'title' => 'Unable to create owner.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3053' => [
        'title' => 'Unable to create property.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3054' => [
        'title' => 'Unable to delete the property.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3055' => [
        'title' => 'Unable to delete the unit.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3056' => [
        'title' => 'Unable to load properties.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3057' => [
        'title' => 'Unable to load property.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3058' => [
        'title' => 'Unable to locate this unit.',
        'what' => 'The step did not finish, so nothing was changed by it.',
        'fix' => 'Reload the page and look at the record before trying again — that shows whether any part of it went through. If it fails a second time, tell us what you were doing.',
    ],

    'PM-3059' => [
        'title' => 'Unable to update property.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3060' => [
        'title' => 'Unable to update unit.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3061' => [
        'title' => 'The same owner cannot be added more than once.',
        'what' => 'The same party is listed twice among the owners of this property.',
        'fix' => 'Remove the duplicate and give the single entry the full share. Two people who share a name are two parties — check you did not pick the same one twice.',
    ],

    'PM-3062' => [
        'title' => 'Every unit must have a name or number.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-3063' => [
        'title' => 'A property must have at least one owner.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-3064' => [
        'title' => 'Property ownership must total exactly 100%.',
        'what' => 'A property is owned exactly once over, so the shares must add up to 100%.',
        'fix' => 'Adjust the percentages until they total exactly 100 — one owner takes 100, two equal owners take 50 and 50.',
    ],

    'PM-3065' => [
        'title' => 'Unit names must be unique within the property.',
        'what' => 'Another record already holds this value, and Patrimoine keeps it unique so the two cannot be confused later.',
        'fix' => 'Search for the existing record first — usually it is the one you meant. If both genuinely exist, distinguish them, for instance by adding a middle name or a unit number.',
    ],

    'PM-3066' => [
        'title' => 'Unit name or number is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3067' => [
        'title' => 'A property must have at least one unit.',
        'what' => 'The form expects a particular number, or a particular shape, and what was entered does not fit it.',
        'fix' => 'The message names the limit. Add, remove or correct entries until it is met — the field will accept the change as soon as it is.',
    ],

    'PM-3068' => [
        'title' => 'A valid property must be selected.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3069' => [
        'title' => 'Tenant fund balances cannot be adjusted below zero.',
        'what' => 'An adjustment cannot take a tenant fund below zero. A negative fund would mean holding money that was never received.',
        'fix' => 'Adjust to zero or above. If money genuinely left the account, record it as a withdrawal or an expense instead — both leave a trace of where it went.',
    ],

    'PM-3070' => [
        'title' => 'The corrected balance is already the current balance.',
        'what' => 'The corrected balance you entered is the balance already recorded, so there is nothing to adjust.',
        'fix' => 'Check the figure again. If the balance is genuinely wrong, enter what it should be rather than what it is.',
    ],

    'PM-3071' => [
        'title' => 'The total exceeds the available fund balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-3072' => [
        'title' => 'The selected Party is not a Tenant.',
        'what' => 'The record chosen is not of the kind this action works on.',
        'fix' => 'Go back and pick from the list offered for this action rather than searching for the record yourself — the list only contains records the action can accept.',
    ],

    'PM-3073' => [
        'title' => 'Payment exceeds the available account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-3074' => [
        'title' => 'Account, amount and date are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3075' => [
        'title' => 'The amount cannot exceed the available Security Deposit balance.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-3076' => [
        'title' => 'The amount cannot exceed the selected receivable outstanding balance.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-3077' => [
        'title' => 'The amount cannot exceed the source account balance.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-3078' => [
        'title' => 'Complete all required transfer fields, including the reason.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3079' => [
        'title' => 'Source and destination accounts must be different.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3080' => [
        'title' => 'Unable to load Tenants.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3081' => [
        'title' => 'Unable to load Tenant details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3082' => [
        'title' => 'Unable to load this Tenant.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3083' => [
        'title' => 'Unable to open invoice.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3084' => [
        'title' => 'Unable to open the transfer receipt.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3085' => [
        'title' => 'Unable to resend invoice.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3086' => [
        'title' => 'Unable to resend receipt.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3087' => [
        'title' => 'Unable to resend the transfer receipt.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3088' => [
        'title' => 'Withdrawal cannot exceed the available balance.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-3089' => [
        'title' => 'Your properties and parties could not be loaded.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3090' => [
        'title' => 'The lease could not be created. Nothing was saved.',
        'what' => 'A request did not come back. Usually the connection dropped or the session ended while the page was open.',
        'fix' => 'Try again, and reload the page if it fails a second time. If it keeps happening, tell us what you were doing.',
    ],

    'PM-3091' => [
        'title' => 'That assistant is no longer there.',
        'what' => 'The unfinished assistant you were continuing has since been finished by somebody, or discarded. Only one copy of it ever existed, and it is gone.',
        'fix' => 'Go back to Leases. If the letting was finished it is in the list; if it was discarded, start the assistant again.',
    ],

    'PM-3092' => [
        'title' => 'This one could not be discarded.',
        'what' => 'The request to throw away an unfinished assistant did not come back. Nothing was changed.',
        'fix' => 'Try again. If it keeps happening, the assistant is doing no harm where it is — tell us and carry on.',
    ],

    /* ---- 4xxx ---- */

    'PM-4001' => [
        'title' => 'Consumable Advance account is closed.',
        'what' => 'The fund account is closed, and a closed account neither receives nor pays out.',
        'fix' => 'Use an open account for this transaction. Fund accounts close when a lease ends, so if the lease is still running, check that you picked the right one.',
    ],

    'PM-4002' => [
        'title' => 'Consumable Advance amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4003' => [
        'title' => 'Consumable Advance cannot be used for a draft Lease.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-4004' => [
        'title' => 'Consumable Advance exceeds the Invoice outstanding amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4005' => [
        'title' => 'Consumable Advance balance is insufficient.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4006' => [
        'title' => 'Consumable Advance can only settle rent invoices.',
        'what' => 'Each kind of fund account has a purpose, and this one is not allowed to settle this kind of invoice.',
        'fix' => 'Use the account intended for it — a rent reserve or consumable advance settles rent; a security deposit is settled at the end of the tenancy, not against invoices.',
    ],

    'PM-4007' => [
        'title' => 'Only a Consumable Advance account can be consumed by this service.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4008' => [
        'title' => 'The Invoice does not belong to the Consumable Advance Lease.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-4009' => [
        'title' => 'Unable to export the Financial Journal.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4010' => [
        'title' => 'Unable to load the Financial Journal.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4011' => [
        'title' => 'Unable to load Journal entry details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4012' => [
        'title' => 'The selected tenant fund account is not a Consumable Advance account.',
        'what' => 'The account chosen is not a consumable advance, and this operation only works on one.',
        'fix' => 'Pick the lease’s consumable advance account from the list. Each lease has three fund accounts, and each does a different job.',
    ],

    'PM-4013' => [
        'title' => 'The selected tenant fund account is not a Rent Reserve account.',
        'what' => 'The account chosen is not a rent reserve, and this operation only works on one.',
        'fix' => 'Pick the lease’s rent reserve account from the list. Each lease has three: rent reserve, consumable advance and security deposit, and each does a different job.',
    ],

    'PM-4014' => [
        'title' => 'This fund account cannot pay rent invoices.',
        'what' => 'Each kind of fund account has a purpose, and this one is not allowed to settle this kind of invoice.',
        'fix' => 'Use the account intended for it — a rent reserve or consumable advance settles rent; a security deposit is settled at the end of the tenancy, not against invoices.',
    ],

    'PM-4015' => [
        'title' => 'The fund account is not active.',
        'what' => 'The fund account is closed, and a closed account neither receives nor pays out.',
        'fix' => 'Use an open account for this transaction. Fund accounts close when a lease ends, so if the lease is still running, check that you picked the right one.',
    ],

    'PM-4016' => [
        'title' => 'This payment has already been cancelled.',
        'what' => 'This entry was cancelled once already. Patrimoine will not cancel it twice, which would reverse the money a second time.',
        'fix' => 'Open the record to see the cancellation and its reason. If a further correction is needed, record a new entry rather than cancelling again.',
    ],

    'PM-4017' => [
        'title' => 'Payment amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4018' => [
        'title' => 'Payment exceeds the outstanding invoice amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4019' => [
        'title' => 'Historical consumptions cannot be cancelled.',
        'what' => 'This entry belongs to the figures Patrimoine started from when your books were opened. Those cannot be cancelled, because there is no earlier state to return to.',
        'fix' => 'Record a correcting entry dated today instead. The history stays readable and the correction is visible for what it is.',
    ],

    'PM-4020' => [
        'title' => 'Payment exceeds the available fund account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4021' => [
        'title' => 'This transaction is not an Invoice account payment.',
        'what' => 'The record chosen is not of the kind this action works on.',
        'fix' => 'Go back and pick from the list offered for this action rather than searching for the record yourself — the list only contains records the action can accept.',
    ],

    'PM-4022' => [
        'title' => 'This Invoice has no account payments to receipt yet.',
        'what' => 'A receipt records money received, and nothing has been paid against this invoice from a fund account yet.',
        'fix' => 'Record the payment first; the receipt then becomes available. If the tenant paid in cash or by transfer rather than from a fund, record it as an ordinary payment instead.',
    ],

    'PM-4023' => [
        'title' => 'This Invoice cannot be paid from a fund account.',
        'what' => 'The option submitted is not one Patrimoine recognises for this kind of record.',
        'fix' => 'Choose one of the options offered in the list rather than typing a value. If you reached this from a saved link or an older browser tab, reload the page and try again.',
    ],

    'PM-4024' => [
        'title' => 'The fund account does not belong to the Invoice\'s Lease.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-4025' => [
        'title' => 'Owner adjustment direction must be credit or debit.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-4026' => [
        'title' => 'Owner adjustment amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4027' => [
        'title' => 'Owner adjustment reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4028' => [
        'title' => 'This expense bill payment has already been cancelled.',
        'what' => 'This entry was cancelled once already. Patrimoine will not cancel it twice, which would reverse the money a second time.',
        'fix' => 'Open the record to see the cancellation and its reason. If a further correction is needed, record a new entry rather than cancelling again.',
    ],

    'PM-4029' => [
        'title' => 'Expense bill payment exceeds the outstanding bill amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4030' => [
        'title' => 'Expense bill payment exceeds the available Payout account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4031' => [
        'title' => 'Expense bill payment amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4032' => [
        'title' => 'A cancellation reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4033' => [
        'title' => 'This expense bill has no payments to receipt yet.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4034' => [
        'title' => 'That account cannot pay an expense bill.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4035' => [
        'title' => 'Cash Receiver could not be determined for this cash owner deposit.',
        'what' => 'Cash has to be received by somebody, and Patrimoine records who. It could not work that out for this entry.',
        'fix' => 'Make sure you are signed in as the person who took the cash — Patrimoine names the signed-in user as the cashier. If somebody else received it, have them record it.',
    ],

    'PM-4036' => [
        'title' => 'An owner deposit cannot be recorded with that payment method.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4037' => [
        'title' => 'Owner deposit amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4038' => [
        'title' => 'An owner deposit cannot be recorded for that purpose.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4039' => [
        'title' => 'Only Owner deposits may generate an Owner deposit receipt.',
        'what' => 'The record chosen is not of the kind this action works on.',
        'fix' => 'Go back and pick from the list offered for this action rather than searching for the record yourself — the list only contains records the action can accept.',
    ],

    'PM-4040' => [
        'title' => 'Historical expense bill settlements cannot be cancelled.',
        'what' => 'This entry belongs to the figures Patrimoine started from when your books were opened. Those cannot be cancelled, because there is no earlier state to return to.',
        'fix' => 'Record a correcting entry dated today instead. The history stays readable and the correction is visible for what it is.',
    ],

    'PM-4041' => [
        'title' => 'This transaction is not an expense bill payment.',
        'what' => 'The record chosen is not of the kind this action works on.',
        'fix' => 'Go back and pick from the list offered for this action rather than searching for the record yourself — the list only contains records the action can accept.',
    ],

    'PM-4042' => [
        'title' => 'Unable to fully allocate owner payout to net available credits.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4043' => [
        'title' => 'Owner payout exceeds available balance.',
        'what' => 'The amount asked for is more than the owner currently has available after fees, VAT and expenses.',
        'fix' => 'Check the balance on the owner account and pay that amount or less. If the balance looks wrong, look at the account statement to see which entries make it up.',
    ],

    'PM-4044' => [
        'title' => 'Owner has no funds available for payout.',
        'what' => 'There is nothing to pay out. An owner can only be paid money that tenants have actually paid — rent that is still owed is not yet theirs.',
        'fix' => 'Record the tenant payments first. The owner’s balance rises as rent is collected, and the payout can then be made.',
    ],

    'PM-4045' => [
        'title' => 'Owner payout amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4046' => [
        'title' => 'Account transfer exceeds the available source account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4047' => [
        'title' => 'Account transfer amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4048' => [
        'title' => 'Account transfer reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4049' => [
        'title' => 'An audit reason is required for every manual adjustment.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4050' => [
        'title' => 'The Cashier could not be determined for this cash deposit.',
        'what' => 'Cash has to be received by somebody, and Patrimoine records who. It could not work that out for this entry.',
        'fix' => 'Make sure you are signed in as the person who took the cash — Patrimoine names the signed-in user as the cashier. If somebody else received it, have them record it.',
    ],

    'PM-4051' => [
        'title' => 'Unable to email the bill.',
        'what' => 'The message could not be handed to the mail service. The document itself was created and is available.',
        'fix' => 'Check that the party has a valid email address, then send again. If the address is right and it still fails, download the document and send it yourself while we look into it.',
    ],

    'PM-4052' => [
        'title' => 'Expense description is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4053' => [
        'title' => 'Payment exceeds the outstanding bill amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4054' => [
        'title' => 'Payment exceeds the available Payout account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4055' => [
        'title' => 'Source account, amount and date are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4056' => [
        'title' => 'Withdrawal cannot exceed the available Payout account balance of {balance}.',
        'what' => 'The amount entered is larger than what is available, and Patrimoine will not record money that is not there.',
        'fix' => 'Check the balance shown beside the field and enter that or less. If the balance looks wrong, open the account history to see what has already come out of it.',
    ],

    'PM-4057' => [
        'title' => 'Unable to cancel the bill payment.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-4058' => [
        'title' => 'Unable to create Owner payout.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4059' => [
        'title' => 'Unable to load property owners.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4060' => [
        'title' => 'Unable to load this Property Owner.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4061' => [
        'title' => 'The statement could not be generated.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4062' => [
        'title' => 'Unable to open the transfer voucher.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4063' => [
        'title' => 'Unable to record the bill payment.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4064' => [
        'title' => 'Unable to record Owner adjustment.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4065' => [
        'title' => 'Unable to record the expense bill.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4066' => [
        'title' => 'Unable to record Owner deposit.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4067' => [
        'title' => 'Unable to record Owner expense.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4068' => [
        'title' => 'Unable to resend the expense bill.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-4069' => [
        'title' => 'Unable to resend the transfer voucher.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-4070' => [
        'title' => 'Unable to record the account transfer.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4071' => [
        'title' => 'Owner deposit was recorded but its receipt could not be resolved.',
        'what' => 'The deposit itself was recorded — the money is in the account — but Patrimoine could not produce its receipt just now.',
        'fix' => 'Open the owner account and print the receipt from the deposit line. If it still will not open, tell us: the money is safe either way, and this is ours to fix.',
    ],

    'PM-4072' => [
        'title' => 'Payment was recorded but its receipt could not be resolved.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4073' => [
        'title' => 'Unable to classify tenant funds.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4074' => [
        'title' => 'Unable to load payments.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4075' => [
        'title' => 'Unable to load Payment funds.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4076' => [
        'title' => 'Unable to load Leases',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4077' => [
        'title' => 'Unable to load Owner details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4078' => [
        'title' => 'Unable to load Tenant Leases.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4079' => [
        'title' => 'Unable to open receipt.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4080' => [
        'title' => 'Unable to record payment.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4081' => [
        'title' => 'Unable to search Owners.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4082' => [
        'title' => 'Unable to search Tenants.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4083' => [
        'title' => 'The Cashier could not be determined for this cash payment.',
        'what' => 'Cash has to be received by somebody, and Patrimoine records who. It could not work that out for this entry.',
        'fix' => 'Make sure you are signed in as the person who took the cash — Patrimoine names the signed-in user as the cashier. If somebody else received it, have them record it.',
    ],

    'PM-4084' => [
        'title' => 'Payment Date is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4085' => [
        'title' => 'Rent Reserve account is closed.',
        'what' => 'The fund account is closed, and a closed account neither receives nor pays out.',
        'fix' => 'Use an open account for this transaction. Fund accounts close when a lease ends, so if the lease is still running, check that you picked the right one.',
    ],

    'PM-4086' => [
        'title' => 'Rent Reserve consumption amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4087' => [
        'title' => 'Rent Reserve cannot be consumed before termination notice.',
        'what' => 'A rent reserve is held for the end of a tenancy, so Patrimoine does not let it settle rent while the lease is still running normally.',
        'fix' => 'Serve notice on the lease first. Once the tenancy is in its notice period, the reserve can be used against the remaining rent.',
    ],

    'PM-4088' => [
        'title' => 'Rent Reserve consumption exceeds the Invoice outstanding amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4089' => [
        'title' => 'Rent Reserve balance is insufficient.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4090' => [
        'title' => 'Building has no ownership allocations.',
        'what' => 'The property has no owner recorded, so Patrimoine cannot work out whose money this is.',
        'fix' => 'Open the property and add its owners with their shares, totalling 100%. Rent collected before that has nowhere to go.',
    ],

    'PM-4091' => [
        'title' => 'Building ownership percentages must total 100%.',
        'what' => 'A property is owned exactly once over. The shares entered add up to more or less than 100%.',
        'fix' => 'Adjust the percentages until they total exactly 100. A single owner takes 100; two owners in half shares take 50 and 50.',
    ],

    'PM-4092' => [
        'title' => 'Rent Reserve can only settle rent invoices.',
        'what' => 'Each kind of fund account has a purpose, and this one is not allowed to settle this kind of invoice.',
        'fix' => 'Use the account intended for it — a rent reserve or consumable advance settles rent; a security deposit is settled at the end of the tenancy, not against invoices.',
    ],

    'PM-4093' => [
        'title' => 'Only a Rent Reserve account can be consumed by this service.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4094' => [
        'title' => 'The Invoice does not belong to the Rent Reserve Lease.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-4095' => [
        'title' => 'No security deposit account exists for this Lease.',
        'what' => 'This lease has no security deposit account, so there is no deposit to work with. Either none was agreed, or the lease was created before deposits were recorded on it.',
        'fix' => 'If a deposit was taken, record it against the lease first. If none was taken, there is nothing to settle — complete the termination without a deposit step.',
    ],

    'PM-4096' => [
        'title' => 'Security deposit has already been settled for this Lease.',
        'what' => 'This deposit has been settled once already, and a settlement is final so the figures cannot move afterwards.',
        'fix' => 'Look at the settlement voucher to see what was deducted and refunded. If it is genuinely wrong, record a correcting entry rather than changing history.',
    ],

    'PM-4097' => [
        'title' => 'Security deposit deductions cannot be changed after final settlement.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4098' => [
        'title' => 'Security deposit deductions can only be recorded for a terminated Lease.',
        'what' => 'Deductions come out of the deposit at the end of a tenancy, so they can only be recorded once the lease is terminated.',
        'fix' => 'Complete the termination first. Deductions are then entered in the settlement, where the deposit, the deductions and the refund are worked out together.',
    ],

    'PM-4099' => [
        'title' => 'Security deposit account has an invalid negative balance.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-4100' => [
        'title' => 'Tenant fund account is not active.',
        'what' => 'The fund account is closed, and a closed account neither receives nor pays out.',
        'fix' => 'Use an open account for this transaction. Fund accounts close when a lease ends, so if the lease is still running, check that you picked the right one.',
    ],

    'PM-4101' => [
        'title' => 'Tenant expense description is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4102' => [
        'title' => 'Expenses cannot be recorded for a draft Lease.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-4103' => [
        'title' => 'Tenant expense exceeds the available fund balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4104' => [
        'title' => 'A tenant expense requires at least one line.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4105' => [
        'title' => 'Tenant expense amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    /* ---- 5xxx ---- */

    'PM-5001' => [
        'title' => 'The selected account is not valid for this transaction.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-5002' => [
        'title' => 'The transaction was not recorded.',
        'what' => 'A request did not come back. Usually the connection dropped or the session ended while the page was open.',
        'fix' => 'Try again, and reload the page if it fails a second time. If it keeps happening, tell us what you were doing.',
    ],

    'PM-5003' => [
        'title' => 'Complete all required transaction fields.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-5004' => [
        'title' => 'Unable to export Activity Log.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-5005' => [
        'title' => 'Unable to load Activity Log.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-5006' => [
        'title' => 'Unable to load activity details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-5007' => [
        'title' => 'Unable to load Tenant financial accounts.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-5008' => [
        'title' => 'This document link is invalid or has expired. Please open the document again.',
        'what' => 'Document links are signed and short-lived so that a copied link cannot be used later by somebody else. This one has expired or was altered.',
        'fix' => 'Go back into Patrimoine and open the document again from where it belongs — the invoice, the receipt or the report. A fresh link is created each time.',
    ],

    'PM-5009' => [
        'title' => 'This document cannot be opened through a signed link.',
        'what' => 'This document is not one Patrimoine opens through a temporary link. The link that got you here points at something it cannot serve that way.',
        'fix' => 'Open the document from where it belongs inside Patrimoine — the invoice, the receipt, the report — rather than from a saved or shared link.',
    ],

    'PM-5010' => [
        'title' => 'The report end date must be on or after the start date.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-5011' => [
        'title' => 'Unable to download report.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-5012' => [
        'title' => 'Unable to generate report.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-5013' => [
        'title' => 'Unable to load Payment Report filters.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-5014' => [
        'title' => 'Unable to open report.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-5015' => [
        'title' => 'Unable to open the Tenant Statement.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-5016' => [
        'title' => 'Unable to search.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    /* ---- 6xxx ---- */

    'PM-6001' => [
        'title' => 'Owner does not have an email address.',
        'what' => 'Patrimoine has no email address for this owner, so there is nowhere to send the document.',
        'fix' => 'Open the owner in Parties and add their email address, then send again. If they have no email, download the document and give it to them another way.',
    ],

    'PM-6002' => [
        'title' => 'Emails to parties are switched off in your organisation settings, so nothing was sent.',
        'what' => 'Your organisation has emails to parties switched off in Settings, so Patrimoine sent nothing. Nothing else was affected: the invoice, receipt or voucher exists and can be downloaded.',
        'fix' => 'An administrator can switch emails back on in Settings under Communications. To email one party while the rest stay silent, set that party to "Always email" on their own record.',
    ],

    'PM-6003' => [
        'title' => 'This party is excluded from Patrimoine emails, so nothing was sent.',
        'what' => 'This party is set to never receive emails from Patrimoine, so nothing was sent. The document itself is unaffected and can be downloaded or printed.',
        'fix' => 'Open the party and change their email setting to "Follow organisation setting" or "Always email this party".',
    ],

    'PM-6004' => [
        'title' => 'Tenant does not have an email address.',
        'what' => 'Patrimoine has no email address for this tenant, so there is nowhere to send the document.',
        'fix' => 'Open the tenant in Parties and add their email address, then send again. If they have no email, download the document and give it to them another way.',
    ],

    'PM-6005' => [
        'title' => 'Unable to load notifications.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    /* ---- 7xxx ---- */

    'PM-7001' => [
        'title' => 'Your plan\'s monthly email allowance has been used up.',
        'what' => 'Your plan includes a fixed number of these, and that number has been used. Nothing already recorded is affected — only adding more is paused.',
        'fix' => 'An administrator can see the usage against each limit on the Licence page and change plan there. Removing records you no longer need also frees room.',
    ],

    'PM-7002' => [
        'title' => 'This feature is not included in your current plan.',
        'what' => 'This part of Patrimoine belongs to a higher plan than the one your organisation is on.',
        'fix' => 'An administrator can see what each plan includes on the Licence page and change plan there. Nothing you have already recorded is affected by the plan you are on.',
    ],

    'PM-7004' => [
        'title' => 'Your plan\'s active lease limit has been reached. Upgrade your plan to add more active leases.',
        'what' => 'Your plan includes a fixed number of these, and that number has been used. Nothing already recorded is affected — only adding more is paused.',
        'fix' => 'An administrator can see the usage against each limit on the Licence page and change plan there. Removing records you no longer need also frees room.',
    ],

    'PM-7005' => [
        'title' => 'Your plan\'s party limit has been reached. Upgrade your plan to add more parties.',
        'what' => 'Your plan includes a fixed number of these, and that number has been used. Nothing already recorded is affected — only adding more is paused.',
        'fix' => 'An administrator can see the usage against each limit on the Licence page and change plan there. Removing records you no longer need also frees room.',
    ],

    'PM-7006' => [
        'title' => 'Unable to load licensing information.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-7007' => [
        'title' => 'Your plan\'s user limit has been reached. Upgrade your plan to add more users.',
        'what' => 'Your plan includes a fixed number of these, and that number has been used. Nothing already recorded is affected — only adding more is paused.',
        'fix' => 'An administrator can see the usage against each limit on the Licence page and change plan there. Removing records you no longer need also frees room.',
    ],

    /* ---- 8xxx ---- */

    'PM-8001' => [
        'title' => 'The configured managing organisation cannot be deleted.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-8002' => [
        'title' => 'Your own company has to stay the managing organisation. Name another party as the managing organisation first.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-8003' => [
        'title' => 'Managing organisation has not been configured.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    /* ---- 9xxx ---- */

    'PM-9001' => [
        'title' => 'Patrimoine could not complete that.',
        'what' => 'The request did not complete. Patrimoine cannot tell from here whether it was the network, the session, or the service itself.',
        'fix' => 'Try again. If it fails a second time, reload the page — that renews the session too. If it still fails, tell us what you were doing and we will look at our side.',
    ],

    'PM-9002' => [
        'title' => 'Your session has expired. Please sign in again.',
        'what' => 'Patrimoine signs you out after a spell without activity, so a screen left open cannot be used by somebody walking past. The page stayed open; the session behind it did not.',
        'fix' => 'Sign in again. Saved work is safe. Anything typed but not yet saved will need to be entered again — worth copying it out of the form before you sign in.',
    ],

    'PM-9003' => [
        'title' => 'Unable to load dashboard information.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-9004' => [
        'title' => 'Unable to load this section.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-9005' => [
        'title' => 'The Managing Organisation is not configured yet. Fill in the form below and save to set it up.',
        'what' => 'Your own company has not been set up yet. Patrimoine puts that name on invoices, receipts and statements, so it needs it before those documents can be produced.',
        'fix' => 'Fill in the organisation form in Settings and save. Only the name and contact details are needed to start; the rest can follow.',
    ],

    'PM-9006' => [
        'title' => 'Unable to export the Registry.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-9007' => [
        'title' => 'Unable to import the backup.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-9008' => [
        'title' => 'Unable to load Managing Organisation.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-9009' => [
        'title' => 'Unable to save Managing Organisation.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],
];

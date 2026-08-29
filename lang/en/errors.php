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
        'title' => 'This sign-in attempt has expired. Sign in again to receive a new code.',
        'what' => 'Sign-in codes are short-lived. Too much time passed between asking for the code and entering it, so it is no longer accepted.',
        'fix' => 'Sign in again with your email address and password. A fresh code is sent immediately, and it is the one to use.',
    ],

    'PM-1002' => [
        'title' => 'This organisation is currently suspended. Contact support@patrimoine365.com.',
        'what' => 'This organisation has been suspended, so nobody in it can sign in. Suspension is applied by Patrimoine, usually over an unpaid subscription or at the organisation’s own request.',
        'fix' => 'Your data is untouched and comes back exactly as it was once the suspension is lifted. Contact us to find out why it was applied and what is needed to restore access.',
    ],

    'PM-1003' => [
        'title' => 'Authentication succeeded but no API token was returned.',
        'what' => 'Your password and code were accepted, but the session that should have followed was not handed over. This is a fault on our side, not a problem with your account.',
        'fix' => 'Try signing in once more. If it happens again, contact us with this code — your account is fine, and we can see what went wrong from our records.',
    ],

    'PM-1004' => [
        'title' => 'Unable to sign in.',
        'what' => 'The sign-in request did not come back. This is not about the password being wrong — Patrimoine never got far enough to check it.',
        'fix' => 'Check that other websites load, then try again. If your connection is fine and this keeps happening, tell us: sign-in failing is ours to fix, not yours.',
    ],

    'PM-1005' => [
        'title' => 'The password confirmation does not match.',
        'what' => 'The two boxes do not contain the same thing. Patrimoine asks twice precisely so a typo cannot lock you out later.',
        'fix' => 'Type it again in both boxes, slowly. If your browser filled one of them in for you, clear both first.',
    ],

    'PM-1006' => [
        'title' => 'This password reset link is invalid or has expired.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-1007' => [
        'title' => 'Unable to complete the password request.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1008' => [
        'title' => 'That file could not be read as an image. Use a JPG, PNG, WEBP or GIF.',
        'what' => 'The file could not be read as a picture. Either it is not an image, or it is in a format this browser cannot decode.',
        'fix' => 'Use a JPG, PNG or WEBP file. HEIC photos straight from an iPhone only work in Safari — on other browsers, export the photo as JPG first.',
    ],

    'PM-1009' => [
        'title' => 'This verification link is invalid or has expired. Request a new one from the sign-in page.',
        'what' => 'Verification links can only be used once, and they expire. This one has already been used or has passed its expiry.',
        'fix' => 'Enter your email address on the verification page and ask for a new link. Open the newest email you receive — older links stay dead.',
    ],

    'PM-1010' => [
        'title' => 'Start your 30-day Professional trial. No payment card required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-1011' => [
        'title' => 'Unable to create your organisation.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1012' => [
        'title' => 'An invitation cannot be sent to an inactive user.',
        'what' => 'The account is switched off, and Patrimoine does not invite somebody to an account they could not use.',
        'fix' => 'Activate the account first on the Users page. The invitation goes out by itself the moment it is activated.',
    ],

    'PM-1013' => [
        'title' => 'This invitation link is invalid or has expired.',
        'what' => 'This invitation link has expired, has already been accepted, or was replaced when a newer invitation was sent to the same person.',
        'fix' => 'Ask an administrator in your organisation to send the invitation again from the Users page, then open the newest email.',
    ],

    'PM-1014' => [
        'title' => 'You cannot change your own Administrator role.',
        'what' => 'An administrator cannot lower their own role, because doing so could leave the organisation with nobody able to restore it.',
        'fix' => 'Ask another administrator to change your role for you.',
    ],

    'PM-1015' => [
        'title' => 'You cannot delete your own account.',
        'what' => 'You cannot delete the account you are signed in with.',
        'fix' => 'Ask another administrator to delete it once you have signed in as somebody else.',
    ],

    'PM-1016' => [
        'title' => 'You cannot disable your own account.',
        'what' => 'You are signed in as this user, and switching off your own account would lock you out mid-action.',
        'fix' => 'Ask another administrator to do it, or sign in as a different administrator first.',
    ],

    'PM-1017' => [
        'title' => 'This action cannot be completed because Patrimoine must retain at least one active Administrator.',
        'what' => 'Every organisation must keep at least one active administrator, otherwise nobody could manage users, settings or licences ever again.',
        'fix' => 'Make somebody else an administrator first, then repeat what you were doing.',
    ],

    'PM-1018' => [
        'title' => 'Platform staff accounts must use an @patrimoine365.com email address.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-1019' => [
        'title' => 'Unable to complete this user action.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1020' => [
        'title' => 'Unable to create user.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1021' => [
        'title' => 'Unable to delete user.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-1022' => [
        'title' => 'Unable to load users.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-1023' => [
        'title' => 'Unable to update user.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-1024' => [
        'title' => 'Link invalid or expired',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-1025' => [
        'title' => 'Unable to send a new link right now.',
        'what' => 'The message could not be handed to the mail service. The document itself was created and is available.',
        'fix' => 'Check that the party has a valid email address, then send again. If the address is right and it still fails, download the document and send it yourself while we look into it.',
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
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
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
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
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
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
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

    'PM-2037' => [
        'title' => 'The this field field must be a number.',
        'what' => 'This field takes a number, and what was entered is not one Patrimoine can read.',
        'fix' => 'Enter digits only — no currency symbol, no letters. Amounts in Patrimoine are whole units of your currency, so leave out decimals.',
    ],

    'PM-2038' => [
        'title' => 'The this field field format is invalid.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2039' => [
        'title' => 'The this field field is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2040' => [
        'title' => 'The this field field is required when :other is :value.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2041' => [
        'title' => 'The this field field is required unless :other is in :values.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2042' => [
        'title' => 'The this field field is required when :values is present.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2043' => [
        'title' => 'The this field field is required when :values is not present.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2044' => [
        'title' => 'The this field field must match :other.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-2045' => [
        'title' => 'The this field field must contain :size items.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-2046' => [
        'title' => 'The this field field must be :size kilobytes.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2047' => [
        'title' => 'The this field field must be :size.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2048' => [
        'title' => 'The this field field must be :size characters.',
        'what' => 'What was entered is too long or too short for this field.',
        'fix' => 'The message gives the limit. Shorten or lengthen the entry to fit. For long explanations, use the notes field, which has room.',
    ],

    'PM-2049' => [
        'title' => 'The this field field must be a string.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2050' => [
        'title' => 'The this field has already been taken.',
        'what' => 'Another record already holds this value, and Patrimoine keeps it unique so the two cannot be confused later.',
        'fix' => 'Search for the existing record first — usually it is the one you meant. If both genuinely exist, distinguish them, for instance by adding a middle name or a unit number.',
    ],

    'PM-2051' => [
        'title' => 'Advance received date cannot be before the Lease start date.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-2052' => [
        'title' => 'Advance Payment must be greater than zero when Advance already received is selected.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-2053' => [
        'title' => 'An Agent is required when an agent commission is configured.',
        'what' => 'A commission has been entered, but no agent is on the lease. Commission is money paid to somebody, so Patrimoine needs to know to whom.',
        'fix' => 'Either choose the agent on the lease, or set the commission back to zero.',
    ],

    'PM-2054' => [
        'title' => 'Selected Party must have the agent role.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2055' => [
        'title' => 'A Building is required when a Unit is selected.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2056' => [
        'title' => 'Management fee value must be zero when management fee type is none.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2057' => [
        'title' => 'Percentage management fee cannot exceed 100%.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-2058' => [
        'title' => 'Termination notice date is required when Lease status is notice.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2059' => [
        'title' => 'Payments cannot be recorded against a draft Lease.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-2060' => [
        'title' => 'Next rent increment date is required when a rent increment is configured.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2061' => [
        'title' => 'Next rent increment date must be empty when no rent increment is configured.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2062' => [
        'title' => 'Rent increment value must be zero when no rent increment is configured.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-2063' => [
        'title' => 'Percentage rent increment cannot exceed 100%.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-2064' => [
        'title' => 'Rent Reserve cannot exceed the total Advance Payment.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-2065' => [
        'title' => 'Selected Party must have the tenant role.',
        'what' => 'The party chosen as tenant is not marked as a tenant, so Patrimoine will not put them on a lease.',
        'fix' => 'Open the party and tick Tenant among their roles, then try again. Creating the lease through the Assistant does this for you.',
    ],

    'PM-2066' => [
        'title' => 'This Unit already has an active Lease.',
        'what' => 'A unit can carry only one live lease at a time, and this one already has an active or notice-period lease on it.',
        'fix' => 'Terminate the existing lease first, or choose a vacant unit. If the previous tenant has already left, complete the termination so the unit is free.',
    ],

    'PM-2067' => [
        'title' => 'Selected Unit does not belong to the selected Building.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-2068' => [
        'title' => 'This property has no recorded owner yet, so the wizard needs at least one.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-2069' => [
        'title' => 'The selected unit does not belong to the selected property.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
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
        'title' => 'Lease cannot be deleted safely.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3004' => [
        'title' => 'This draft Lease cannot be deleted because contractual or financial history references it. Keep the Lease record.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-3005' => [
        'title' => 'The configured Managing Organisation cannot be deleted. Change the Managing Organisation configuration instead.',
        'what' => 'This party is your own company — the one whose name appears on invoices, receipts and statements. Patrimoine cannot delete it while it holds that position.',
        'fix' => 'If your company details are wrong, edit them in Settings. To hand the position to a different party, set that party as the managing organisation first.',
    ],

    'PM-3006' => [
        'title' => 'This Party cannot be deleted because it is referenced by Lease, ownership, agency or financial history. Keep the Party so historical records remain understandable.',
        'what' => 'This party appears in leases, ownership, agency or financial history. Deleting it would leave those records pointing at nobody, so Patrimoine keeps it.',
        'fix' => 'Keep the party — that is what makes the old records readable. If they are no longer someone you deal with, remove their roles instead, or simply leave them; a party with no active lease costs nothing.',
    ],

    'PM-3007' => [
        'title' => 'This Unit cannot be deleted because Lease or financial history references it. Keep the Unit and terminate the Lease instead where applicable.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3008' => [
        'title' => 'Unable to record the deduction.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3009' => [
        'title' => 'This Lease cannot be deleted safely.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-3010' => [
        'title' => 'You must type DELETE exactly.',
        'what' => 'The confirmation text does not match what was asked for. It is deliberately awkward: it is the last thing standing between a slip of the hand and permanent deletion.',
        'fix' => 'Type it exactly as shown, in the same capitals, with no extra spaces.',
    ],

    'PM-3011' => [
        'title' => 'Unable to calculate the Lease deletion impact.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3012' => [
        'title' => 'Your current password is required.',
        'what' => 'Patrimoine asks for your own password before something irreversible, so that an unattended screen cannot be used to destroy records.',
        'fix' => 'Type the password you sign in with. If you have forgotten it, sign out and use "Forgotten password" to set a new one, then come back.',
    ],

    'PM-3013' => [
        'title' => 'A deletion reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-3014' => [
        'title' => 'Unable to load financial history.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3015' => [
        'title' => 'Unable to open document.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3016' => [
        'title' => 'Unable to cancel the rent increment.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3017' => [
        'title' => 'Unable to schedule the rent increment.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3018' => [
        'title' => 'Unable to load rent increments.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3019' => [
        'title' => 'Rent Reserve cannot exceed Total Advance Payment.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3020' => [
        'title' => 'Unable to cancel termination.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3021' => [
        'title' => 'Unable to complete termination.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3022' => [
        'title' => 'Unable to initiate Lease termination.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3023' => [
        'title' => 'Unable to open the Termination Notice.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3024' => [
        'title' => 'Notice Date, Termination Date and final rental treatment are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3025' => [
        'title' => 'Unable to load the termination settlement.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3026' => [
        'title' => 'Items that must be resolved',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3027' => [
        'title' => 'Unable to add Security Deposit deduction.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3028' => [
        'title' => 'Unable to apply Consumable Advance.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3029' => [
        'title' => 'Unable to apply Rent Reserve.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3030' => [
        'title' => 'Unable to create Lease.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3031' => [
        'title' => 'Unable to delete Lease.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3032' => [
        'title' => 'Unable to finalize Security Deposit.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3033' => [
        'title' => 'Unable to initialize Leases.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3034' => [
        'title' => 'Unable to load Leases.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3035' => [
        'title' => 'Unable to load Lease.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3036' => [
        'title' => 'Unable to load Security Deposit.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3037' => [
        'title' => 'Unable to load Tenant Funds.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3038' => [
        'title' => 'Unable to open Security Deposit voucher.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3039' => [
        'title' => 'Unable to update Lease.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3040' => [
        'title' => 'The voucher could not be opened because the browser blocked the new tab.',
        'what' => 'A request did not come back. Usually the connection dropped or the session ended while the page was open.',
        'fix' => 'Try again, and reload the page if it fails a second time. If it keeps happening, tell us what you were doing.',
    ],

    'PM-3041' => [
        'title' => 'Only an unreferenced Party can be deleted. Parties used by leases, ownership, agency or financial history must be retained.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3042' => [
        'title' => 'Unable to create Party.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3043' => [
        'title' => 'Unable to delete Party.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3044' => [
        'title' => 'Unable to load parties.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3045' => [
        'title' => 'Unable to load Party.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3046' => [
        'title' => 'Unable to update Party.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3047' => [
        'title' => 'Legal name and contact person details are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3048' => [
        'title' => 'Name, phone and email are required for a person.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3049' => [
        'title' => 'Unable to add unit.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3050' => [
        'title' => 'Unable to create owner.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3051' => [
        'title' => 'Unable to create property.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3052' => [
        'title' => 'Unable to delete the property.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3053' => [
        'title' => 'Unable to delete the unit.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3054' => [
        'title' => 'Unable to load properties.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3055' => [
        'title' => 'Unable to load property.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3056' => [
        'title' => 'Unable to locate this unit.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3057' => [
        'title' => 'Unable to update property.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3058' => [
        'title' => 'Unable to update unit.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-3059' => [
        'title' => 'The same owner cannot be added more than once.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3060' => [
        'title' => 'Every unit must have a name or number.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3061' => [
        'title' => 'A property must have at least one owner.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3062' => [
        'title' => 'Property ownership must total exactly 100%.',
        'what' => 'A property is owned exactly once over, so the shares must add up to 100%.',
        'fix' => 'Adjust the percentages until they total exactly 100 — one owner takes 100, two equal owners take 50 and 50.',
    ],

    'PM-3063' => [
        'title' => 'Unit names must be unique within the property.',
        'what' => 'Another record already holds this value, and Patrimoine keeps it unique so the two cannot be confused later.',
        'fix' => 'Search for the existing record first — usually it is the one you meant. If both genuinely exist, distinguish them, for instance by adding a middle name or a unit number.',
    ],

    'PM-3064' => [
        'title' => 'Unit name or number is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3065' => [
        'title' => 'A property must have at least one unit.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3066' => [
        'title' => 'A valid property must be selected.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3067' => [
        'title' => 'Tenant fund balances cannot be adjusted below zero.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3068' => [
        'title' => 'The corrected balance is already the current balance.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3069' => [
        'title' => 'The total exceeds the available fund balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-3070' => [
        'title' => 'The selected Party is not a Tenant.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3071' => [
        'title' => 'Payment exceeds the available account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-3072' => [
        'title' => 'Account, amount and date are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3073' => [
        'title' => 'The amount cannot exceed the available Security Deposit balance.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3074' => [
        'title' => 'The amount cannot exceed the selected receivable outstanding balance.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3075' => [
        'title' => 'The amount cannot exceed the source account balance.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3076' => [
        'title' => 'Complete all required transfer fields, including the reason.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-3077' => [
        'title' => 'Source and destination accounts must be different.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-3078' => [
        'title' => 'Unable to load Tenants.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3079' => [
        'title' => 'Unable to load Tenant details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3080' => [
        'title' => 'Unable to load this Tenant.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3081' => [
        'title' => 'Unable to open invoice.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3082' => [
        'title' => 'Unable to open the transfer receipt.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-3083' => [
        'title' => 'Unable to resend invoice.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3084' => [
        'title' => 'Unable to resend receipt.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3085' => [
        'title' => 'Unable to resend the transfer receipt.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-3086' => [
        'title' => 'Withdrawal cannot exceed the available balance.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-3087' => [
        'title' => 'No planned increase',
        'what' => 'This action needs a level of access, or a plan, that the account does not currently have.',
        'fix' => 'Ask an administrator in your organisation. They can change roles on the Users page and see the plan and its limits on the Licence page.',
    ],

    'PM-3088' => [
        'title' => 'Your properties and parties could not be loaded.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-3089' => [
        'title' => 'The lease could not be created. Nothing was saved.',
        'what' => 'A request did not come back. Usually the connection dropped or the session ended while the page was open.',
        'fix' => 'Try again, and reload the page if it fails a second time. If it keeps happening, tell us what you were doing.',
    ],

    'PM-3090' => [
        'title' => 'Only vacant units are listed. A unit can carry one active lease at a time.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    /* ---- 4xxx ---- */

    'PM-4001' => [
        'title' => 'Consumable Advance amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4002' => [
        'title' => 'Consumable Advance cannot be used for a draft Lease.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-4003' => [
        'title' => 'Consumable Advance exceeds the Invoice outstanding amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4004' => [
        'title' => 'Consumable Advance can only settle rent invoices.',
        'what' => 'Each kind of fund account has a purpose, and this one is not allowed to settle this kind of invoice.',
        'fix' => 'Use the account intended for it — a rent reserve or consumable advance settles rent; a security deposit is settled at the end of the tenancy, not against invoices.',
    ],

    'PM-4005' => [
        'title' => 'The Invoice does not belong to the Consumable Advance Lease.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-4006' => [
        'title' => 'Unable to export the Financial Journal.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4007' => [
        'title' => 'Unable to load the Financial Journal.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4008' => [
        'title' => 'Unable to load Journal entry details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4009' => [
        'title' => 'The selected tenant fund account is not a Consumable Advance account.',
        'what' => 'The account chosen is not a consumable advance, and this operation only works on one.',
        'fix' => 'Pick the lease’s consumable advance account from the list. Each lease has three fund accounts, and each does a different job.',
    ],

    'PM-4010' => [
        'title' => 'The selected tenant fund account is not a Rent Reserve account.',
        'what' => 'The account chosen is not a rent reserve, and this operation only works on one.',
        'fix' => 'Pick the lease’s rent reserve account from the list. Each lease has three: rent reserve, consumable advance and security deposit, and each does a different job.',
    ],

    'PM-4011' => [
        'title' => 'This fund account cannot pay rent invoices.',
        'what' => 'Each kind of fund account has a purpose, and this one is not allowed to settle this kind of invoice.',
        'fix' => 'Use the account intended for it — a rent reserve or consumable advance settles rent; a security deposit is settled at the end of the tenancy, not against invoices.',
    ],

    'PM-4012' => [
        'title' => 'The fund account is not active.',
        'what' => 'The fund account is closed, and a closed account neither receives nor pays out.',
        'fix' => 'Use an open account for this transaction. Fund accounts close when a lease ends, so if the lease is still running, check that you picked the right one.',
    ],

    'PM-4013' => [
        'title' => 'This payment has already been cancelled.',
        'what' => 'This entry was cancelled once already. Patrimoine will not cancel it twice, which would reverse the money a second time.',
        'fix' => 'Open the record to see the cancellation and its reason. If a further correction is needed, record a new entry rather than cancelling again.',
    ],

    'PM-4014' => [
        'title' => 'Payment amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4015' => [
        'title' => 'Payment exceeds the outstanding Invoice amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4016' => [
        'title' => 'Historical consumptions cannot be cancelled.',
        'what' => 'This entry belongs to the figures Patrimoine started from when your books were opened. Those cannot be cancelled, because there is no earlier state to return to.',
        'fix' => 'Record a correcting entry dated today instead. The history stays readable and the correction is visible for what it is.',
    ],

    'PM-4017' => [
        'title' => 'Payment exceeds the available fund account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4018' => [
        'title' => 'This transaction is not an Invoice account payment.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4019' => [
        'title' => 'This Invoice has no account payments to receipt yet.',
        'what' => 'A receipt records money received, and nothing has been paid against this invoice from a fund account yet.',
        'fix' => 'Record the payment first; the receipt then becomes available. If the tenant paid in cash or by transfer rather than from a fund, record it as an ordinary payment instead.',
    ],

    'PM-4020' => [
        'title' => 'This Invoice cannot be paid from a fund account.',
        'what' => 'The option submitted is not one Patrimoine recognises for this kind of record.',
        'fix' => 'Choose one of the options offered in the list rather than typing a value. If you reached this from a saved link or an older browser tab, reload the page and try again.',
    ],

    'PM-4021' => [
        'title' => 'The fund account does not belong to the Invoice\'s Lease.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-4022' => [
        'title' => 'Owner adjustment direction must be credit or debit.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-4023' => [
        'title' => 'Owner adjustment amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4024' => [
        'title' => 'Owner adjustment reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4025' => [
        'title' => 'This expense bill payment has already been cancelled.',
        'what' => 'This entry was cancelled once already. Patrimoine will not cancel it twice, which would reverse the money a second time.',
        'fix' => 'Open the record to see the cancellation and its reason. If a further correction is needed, record a new entry rather than cancelling again.',
    ],

    'PM-4026' => [
        'title' => 'Expense bill payment exceeds the outstanding bill amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4027' => [
        'title' => 'Expense bill payment exceeds the available Payout account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4028' => [
        'title' => 'Expense bill payment amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4029' => [
        'title' => 'A cancellation reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4030' => [
        'title' => 'This expense bill has no payments to receipt yet.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4031' => [
        'title' => 'Unsupported expense bill payment source account.',
        'what' => 'The option submitted is not one Patrimoine recognises for this kind of record.',
        'fix' => 'Choose one of the options offered in the list rather than typing a value. If you reached this from a saved link or an older browser tab, reload the page and try again.',
    ],

    'PM-4032' => [
        'title' => 'Cash Receiver could not be determined for this cash owner deposit.',
        'what' => 'Cash has to be received by somebody, and Patrimoine records who. It could not work that out for this entry.',
        'fix' => 'Make sure you are signed in as the person who took the cash — Patrimoine names the signed-in user as the cashier. If somebody else received it, have them record it.',
    ],

    'PM-4033' => [
        'title' => 'Unsupported owner deposit payment method.',
        'what' => 'The option submitted is not one Patrimoine recognises for this kind of record.',
        'fix' => 'Choose one of the options offered in the list rather than typing a value. If you reached this from a saved link or an older browser tab, reload the page and try again.',
    ],

    'PM-4034' => [
        'title' => 'Owner deposit amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4035' => [
        'title' => 'Unsupported owner deposit purpose.',
        'what' => 'The option submitted is not one Patrimoine recognises for this kind of record.',
        'fix' => 'Choose one of the options offered in the list rather than typing a value. If you reached this from a saved link or an older browser tab, reload the page and try again.',
    ],

    'PM-4036' => [
        'title' => 'Historical expense bill settlements cannot be cancelled.',
        'what' => 'This entry belongs to the figures Patrimoine started from when your books were opened. Those cannot be cancelled, because there is no earlier state to return to.',
        'fix' => 'Record a correcting entry dated today instead. The history stays readable and the correction is visible for what it is.',
    ],

    'PM-4037' => [
        'title' => 'This transaction is not an expense bill payment.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4038' => [
        'title' => 'Unable to fully allocate owner payout to net available credits.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4039' => [
        'title' => 'Owner payout exceeds available balance.',
        'what' => 'The amount asked for is more than the owner currently has available after fees, VAT and expenses.',
        'fix' => 'Check the balance on the owner account and pay that amount or less. If the balance looks wrong, look at the account statement to see which entries make it up.',
    ],

    'PM-4040' => [
        'title' => 'Owner has no funds available for payout.',
        'what' => 'There is nothing to pay out. An owner can only be paid money that tenants have actually paid — rent that is still owed is not yet theirs.',
        'fix' => 'Record the tenant payments first. The owner’s balance rises as rent is collected, and the payout can then be made.',
    ],

    'PM-4041' => [
        'title' => 'Owner payout amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4042' => [
        'title' => 'Account transfer exceeds the available source account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4043' => [
        'title' => 'Account transfer amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4044' => [
        'title' => 'Account transfer reason is required.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4045' => [
        'title' => 'An audit reason is required for every manual adjustment.',
        'what' => 'Cancelling or correcting money already recorded needs a reason, because the reason is what makes the entry understandable to whoever reads the books later.',
        'fix' => 'Write a short line saying why — "paid twice by the tenant", "wrong account chosen" — and save again.',
    ],

    'PM-4046' => [
        'title' => 'The Cashier could not be determined for this cash deposit.',
        'what' => 'Cash has to be received by somebody, and Patrimoine records who. It could not work that out for this entry.',
        'fix' => 'Make sure you are signed in as the person who took the cash — Patrimoine names the signed-in user as the cashier. If somebody else received it, have them record it.',
    ],

    'PM-4047' => [
        'title' => 'Unable to email the bill.',
        'what' => 'The message could not be handed to the mail service. The document itself was created and is available.',
        'fix' => 'Check that the party has a valid email address, then send again. If the address is right and it still fails, download the document and send it yourself while we look into it.',
    ],

    'PM-4048' => [
        'title' => 'Every line needs a description and a whole amount greater than zero.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-4049' => [
        'title' => 'Expense description is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4050' => [
        'title' => 'Payment exceeds the outstanding bill amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4051' => [
        'title' => 'Payment exceeds the available Payout account balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4052' => [
        'title' => 'Source account, amount and date are required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4053' => [
        'title' => 'Withdrawal cannot exceed the available Payout account balance of {balance}.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4054' => [
        'title' => 'Unable to cancel the bill payment.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-4055' => [
        'title' => 'Unable to create Owner payout.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4056' => [
        'title' => 'Unable to load Property Owners.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4057' => [
        'title' => 'Unable to load this Property Owner.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4058' => [
        'title' => 'The statement could not be generated.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4059' => [
        'title' => 'Unable to open the transfer voucher.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4060' => [
        'title' => 'Unable to record the bill payment.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4061' => [
        'title' => 'Unable to record Owner adjustment.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4062' => [
        'title' => 'Unable to record the expense bill.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4063' => [
        'title' => 'Unable to record Owner deposit.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4064' => [
        'title' => 'Unable to record Owner expense.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4065' => [
        'title' => 'Unable to resend the expense bill.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-4066' => [
        'title' => 'Unable to resend the transfer voucher.',
        'what' => 'The request reached Patrimoine but did not complete, so nothing changed.',
        'fix' => 'Reload the page and check whether it went through after all before trying a second time. If it fails again, tell us what you were doing.',
    ],

    'PM-4067' => [
        'title' => 'Unable to record the account transfer.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4068' => [
        'title' => 'Owner deposit was recorded but its receipt could not be resolved.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4069' => [
        'title' => 'Payment was recorded but its receipt could not be resolved.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4070' => [
        'title' => 'Unable to classify tenant funds.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4071' => [
        'title' => 'Unable to load payments.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4072' => [
        'title' => 'Unable to load Payment funds.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4073' => [
        'title' => 'Unable to load Leases',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4074' => [
        'title' => 'Unable to load Owner details.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4075' => [
        'title' => 'Unable to load Tenant Leases.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4076' => [
        'title' => 'Unable to open receipt.',
        'what' => 'The document could not be produced or delivered. The underlying records are safe — this is about making the file, not the data in it.',
        'fix' => 'Try again, and reload the page if it fails twice. If one particular document keeps failing while others work, tell us which one: that points at something specific we can fix.',
    ],

    'PM-4077' => [
        'title' => 'Unable to record payment.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
    ],

    'PM-4078' => [
        'title' => 'Unable to search Owners.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4079' => [
        'title' => 'Unable to search Tenants.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    'PM-4080' => [
        'title' => 'The Cashier could not be determined for this cash payment.',
        'what' => 'Cash has to be received by somebody, and Patrimoine records who. It could not work that out for this entry.',
        'fix' => 'Make sure you are signed in as the person who took the cash — Patrimoine names the signed-in user as the cashier. If somebody else received it, have them record it.',
    ],

    'PM-4081' => [
        'title' => 'Payment Date is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4082' => [
        'title' => 'Rent Reserve consumption amount must be greater than zero.',
        'what' => 'Patrimoine will not record a transaction of zero or a negative amount, because it would move no money while still appearing in the accounts.',
        'fix' => 'Enter an amount greater than zero. To reverse something already recorded, cancel the original entry instead of entering a negative one.',
    ],

    'PM-4083' => [
        'title' => 'Rent Reserve cannot be consumed before termination notice.',
        'what' => 'A rent reserve is held for the end of a tenancy, so Patrimoine does not let it settle rent while the lease is still running normally.',
        'fix' => 'Serve notice on the lease first. Once the tenancy is in its notice period, the reserve can be used against the remaining rent.',
    ],

    'PM-4084' => [
        'title' => 'Rent Reserve consumption exceeds the Invoice outstanding amount.',
        'what' => 'The amount is more than is still owed. Paying more than the outstanding balance would leave the invoice overpaid.',
        'fix' => 'Enter the outstanding amount or less. If the tenant genuinely paid more, record the excess separately — as a fund deposit, not against this invoice.',
    ],

    'PM-4085' => [
        'title' => 'Building has no ownership allocations.',
        'what' => 'The property has no owner recorded, so Patrimoine cannot work out whose money this is.',
        'fix' => 'Open the property and add its owners with their shares, totalling 100%. Rent collected before that has nowhere to go.',
    ],

    'PM-4086' => [
        'title' => 'Building ownership percentages must total 100%.',
        'what' => 'A property is owned exactly once over. The shares entered add up to more or less than 100%.',
        'fix' => 'Adjust the percentages until they total exactly 100. A single owner takes 100; two owners in half shares take 50 and 50.',
    ],

    'PM-4087' => [
        'title' => 'Rent Reserve can only settle rent invoices.',
        'what' => 'Each kind of fund account has a purpose, and this one is not allowed to settle this kind of invoice.',
        'fix' => 'Use the account intended for it — a rent reserve or consumable advance settles rent; a security deposit is settled at the end of the tenancy, not against invoices.',
    ],

    'PM-4088' => [
        'title' => 'The Invoice does not belong to the Rent Reserve Lease.',
        'what' => 'The account and the invoice belong to different leases. Money held for one tenancy cannot settle another tenancy’s invoice.',
        'fix' => 'Choose an account that belongs to the same lease as the invoice. If money really needs to move between leases, use a transfer, which is recorded as one.',
    ],

    'PM-4089' => [
        'title' => 'No security deposit account exists for this Lease.',
        'what' => 'This lease has no security deposit account, so there is no deposit to work with. Either none was agreed, or the lease was created before deposits were recorded on it.',
        'fix' => 'If a deposit was taken, record it against the lease first. If none was taken, there is nothing to settle — complete the termination without a deposit step.',
    ],

    'PM-4090' => [
        'title' => 'Security deposit has already been settled for this Lease.',
        'what' => 'This deposit has been settled once already, and a settlement is final so the figures cannot move afterwards.',
        'fix' => 'Look at the settlement voucher to see what was deducted and refunded. If it is genuinely wrong, record a correcting entry rather than changing history.',
    ],

    'PM-4091' => [
        'title' => 'Security deposit deductions cannot be changed after final settlement.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    'PM-4092' => [
        'title' => 'Security deposit deductions can only be recorded for a terminated Lease.',
        'what' => 'Deductions come out of the deposit at the end of a tenancy, so they can only be recorded once the lease is terminated.',
        'fix' => 'Complete the termination first. Deductions are then entered in the settlement, where the deposit, the deductions and the refund are worked out together.',
    ],

    'PM-4093' => [
        'title' => 'Security deposit account has an invalid negative balance.',
        'what' => 'What was entered is not in the shape this field accepts.',
        'fix' => 'Read the message — it names the field and the shape expected — correct that field and save again.',
    ],

    'PM-4094' => [
        'title' => 'Tenant fund account is not active.',
        'what' => 'The fund account is closed, and a closed account neither receives nor pays out.',
        'fix' => 'Use an open account for this transaction. Fund accounts close when a lease ends, so if the lease is still running, check that you picked the right one.',
    ],

    'PM-4095' => [
        'title' => 'Tenant expense description is required.',
        'what' => 'Something the form needs was left empty.',
        'fix' => 'Fill in the field named in the message and save again. Fields marked with a red asterisk are the ones Patrimoine cannot do without.',
    ],

    'PM-4096' => [
        'title' => 'Expenses cannot be recorded for a draft Lease.',
        'what' => 'The lease is still a draft. A draft records the agreement but has no financial life yet, so money cannot move through it.',
        'fix' => 'Activate the lease from the Leases page. Activation generates the invoices due so far and opens its fund accounts.',
    ],

    'PM-4097' => [
        'title' => 'Tenant expense exceeds the available fund balance.',
        'what' => 'The amount asked for is more than the account holds. Patrimoine does not let an account go below zero, because that money does not exist.',
        'fix' => 'Check the balance shown on the account and enter that amount or less. If the balance is lower than expected, open the account history to see what has already been taken out.',
    ],

    'PM-4098' => [
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
        'title' => 'Unable to complete the transaction.',
        'what' => 'The request reached Patrimoine but did not finish, so nothing was saved. Whatever you were recording is unchanged.',
        'fix' => 'Check the form for a field marked in red, then try again. If there is nothing marked and it keeps failing, tell us what you were recording.',
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
        'title' => 'Tenant does not have an email address.',
        'what' => 'Patrimoine has no email address for this tenant, so there is nowhere to send the document.',
        'fix' => 'Open the tenant in Parties and add their email address, then send again. If they have no email, download the document and give it to them another way.',
    ],

    'PM-6003' => [
        'title' => 'Unable to load notifications.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    /* ---- 7xxx ---- */

    'PM-7001' => [
        'title' => 'This feature is not included in your current plan.',
        'what' => 'This part of Patrimoine belongs to a higher plan than the one your organisation is on.',
        'fix' => 'An administrator can see what each plan includes on the Licence page and change plan there. Nothing you have already recorded is affected by the plan you are on.',
    ],

    'PM-7002' => [
        'title' => 'Unable to load licensing information.',
        'what' => 'Patrimoine asked the server for this information and no answer came back. Usually the connection dropped, or the session ended while the page was open.',
        'fix' => 'Try again. If it happens twice, reload the page — that also renews an expired session. If it keeps happening, your connection or our service is at fault, so tell us.',
    ],

    /* ---- 8xxx ---- */

    'PM-8001' => [
        'title' => 'The configured managing organisation cannot be deleted.',
        'what' => 'Other records depend on this one. Deleting it would break the history that explains where money came from and went.',
        'fix' => 'Open the record to see what refers to it and deal with those first. Often the right answer is to keep it: history stays readable and nothing is charged for records you no longer use.',
    ],

    'PM-8002' => [
        'title' => 'The configured managing organisation cannot lose the managing_organisation role.',
        'what' => 'Patrimoine stopped the action because a rule of the application was not met. Nothing was saved.',
        'fix' => 'Read the message on screen: it names what is wrong. Correct it and try again.',
    ],

    /* ---- 9xxx ---- */

    'PM-9001' => [
        'title' => 'The request could not be completed.',
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

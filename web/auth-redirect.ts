// Confirmation and recovery emails may use the site's root URL.
if (/(?:confirmation_token|recovery_token|invite_token|access_token|email_change_token)=/.test(window.location.hash)) {
 window.location.replace('/portal/' + window.location.hash);
}

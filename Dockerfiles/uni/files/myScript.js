function esegui(){
  // Form reference:
  yWindow = window.open("", "_self");
  var theForm = document.forms['usernamePasswordLoginForm'];

  // Add data:
  addHidden(theForm, 'josso_cmd', 'login');
  addHidden(theForm, 'josso_username', '');
  addHidden(theForm, 'josso_password', '');

  // Submit the form:
  theForm.submit();
  myWindow.document.write("");
  myWindow.close();
}
function addHidden(theForm, key, value) {
    // Create a hidden input element, and append it to the form:
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;'name-as-seen-at-the-server';
    input.value = value;
    theForm.appendChild(input);
}


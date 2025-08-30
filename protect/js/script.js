var theme=readCookie('theme');
$(document).ready(function() {
    console.log(theme);
if(theme=='dark'){
$(document).find(".bg-theme").addClass("bg-[var(--dark-bg)]").removeClass("bg-[var(--light-bg)]");
}else{
$(document).find(".bg-theme").addClass("bg-[var(--light-bg)]").removeClass("bg-[var(--dark-bg)]");
}
});
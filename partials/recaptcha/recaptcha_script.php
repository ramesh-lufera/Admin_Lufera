<?php

require_once __DIR__ . '/recaptcha.php';

?>

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>

<script>

function generateRecaptchaToken(action)
{
    return new Promise(function(resolve, reject)
    {
        grecaptcha.ready(function()
        {
            grecaptcha.execute(
                '<?php echo RECAPTCHA_SITE_KEY; ?>',
                {
                    action: action
                }
            )
            .then(function(token)
            {
                const input = document.getElementById("g-recaptcha-response");

                if (input) {
                    input.value = token;
                }

                resolve(token);
            })
            .catch(function(error)
            {
                reject(error);
            });
        });
    });
}

</script>

<script>
window.addEventListener("load", function () {
    const badge = document.querySelector(".grecaptcha-badge");

    if (badge) {
        badge.style.display = "none";
    }
});
</script>
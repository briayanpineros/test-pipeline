(function ($) {

  Drupal.behaviors.myModule = {
    attach: function (context, settings) {
      once('myBehavior', 'html').forEach(function (element) {
        const synth = window.speechSynthesis;
        const utterThis = new SpeechSynthesisUtterance();

        const voiceSelect = $(settings.selector);

        let flag = 0;
        let voices = [];
        let actual = 0;
        let news = [];
        let timeout = null;

        function populateVoiceList() {
          voices = synth.getVoices().sort(function (a, b) {
            const aname = a.name.toUpperCase();
            const bname = b.name.toUpperCase();

            if (aname < bname) {
              return -1;
            } else if (aname == bname) {
              return 0;
            } else {
              return +1;
            }
          });

        }

        populateVoiceList();

        if (speechSynthesis.onvoiceschanged !== undefined) {
          speechSynthesis.onvoiceschanged = populateVoiceList;
        }

        function calculateNews() {
          if (news != actual) {
            actual = actual + 1;
          }
          else {
            reset();
          }
        }

        function reset() {
          actual = 0;
        }

        function speak() {
          setTimeout(function () {
            if (synth.speaking) {
              synth.cancel();
              speak();
            }
            else {
              if ($('.' + settings.class).length != 0) {
                inputTxt = $('.' + settings.class);

                inputTxt.each(function (index, value) {
                  if (value.classList.contains("welcome-speech")) {
                    inputwelcome = inputTxt.get(index);
                    inputTxt.splice(0, 0, inputwelcome);
                    inputTxt.splice(index + 1, 1);
                  }
                });

                news = inputTxt.length - 1;

                if (inputTxt[actual].textContent === '' || inputTxt[actual].textContent === undefined) {
                  actual = actual + 1;
                }
                utterThis.text = inputTxt[actual].textContent;

                utterThis.onend = function (event) {
                  calculateNews();
                  if (flag == 0) {
                    timeout = setTimeout(reset, settings.seconds * 1000);
                  }
                  else {
                    flag = 0;
                  }
                  console.log("SpeechSynthesisUtterance.onend");
                };

                utterThis.onerror = function (event) {
                  calculateNews();
                  console.error("SpeechSynthesisUtterance.onerror");
                };

                for (let i = 0; i < voices.length; i++) {
                  if (navigator.userAgent.includes('Firefox')) {
                    if (voices[i].lang === $("#edit-lang-dropdown-select").val()) {
                      utterThis.voice = voices[i];
                      break;
                    }
                  }
                  else {
                    if ($("#edit-lang-dropdown-select").val() == 'en') {
                      if (voices[i].lang === $("#edit-lang-dropdown-select").val() + "-GB") {
                        utterThis.voice = voices[i];
                        break;
                      }
                    }
                    else {
                      if (voices[i].lang === $("#edit-lang-dropdown-select").val() + "-" + $("#edit-lang-dropdown-select").val().toUpperCase()) {
                        utterThis.voice = voices[i];
                        break;
                      }
                    }
                  }
                }
                utterThis.pitch = settings.pitch;
                utterThis.rate = settings.rate;
                synth.speak(utterThis);
              }
            }
          }, 500);
        }

        $('.btn-play-speech').on('click', function () {
          if (synth.speaking) {
            flag = 1;
          }
          else {
            flag = 0;
          }
          speak();
          clearTimeout(timeout);
        });
      })
    }
  }
})(jQuery);

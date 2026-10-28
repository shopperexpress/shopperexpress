// Add user journey to the input from the local storage
export default function updateJourneyField() {
  const userJourney = localStorage.getItem('UserJourney');
  const userJourneyField = document.querySelectorAll('.userjourney input');

  if (userJourney && userJourneyField.length) {
    userJourneyField.forEach((elem) => {
      elem.value = userJourney;
    });
  }
}

const validFirstChars = new Set(['"', "'", "«", "»", "“", "”", "‘", "’"]);
const validSpecialChars = new Set([
  ...validFirstChars,
  "(",
  ")",
  "'",
  "!",
  ":",
  ";",
  "/",
  "*",
  "%",
  "-",
  "–",
  "—",
]);

const russianLetterRegex = /[\u0400-\u04FF]/;
const englishLetterRegex = /[A-Za-z]/;
const chineseLetterRegex = /[\u4E00-\u9FFF]/;
const russianDiacriticalRegex = /[\u0300-\u0302]/;
const englishDiacriticalRegex = /[\u0300-\u0304]/;

export const validateCorrectFormText = (
  text: string,
  firstLetterOnly = false,
) => {
  if (text.length === 0) {
    return false;
  }

  let hasLetter = false;
  let previousWasDiacritical = false;
  const firstChar = text[0];
  if (
    !validFirstChars.has(firstChar) &&
    !/\s/.test(firstChar) &&
    !russianLetterRegex.test(firstChar) &&
    !englishLetterRegex.test(firstChar) &&
    !chineseLetterRegex.test(firstChar)
  ) {
    return false;
  }

  if (firstLetterOnly) {
    return true;
  }

  for (let i = 0; i < text.length; i++) {
    const char = text[i];
    if (
      russianLetterRegex.test(char) ||
      englishLetterRegex.test(char) ||
      chineseLetterRegex.test(char)
    ) {
      hasLetter = true;
      previousWasDiacritical = false;
      continue;
    }

    if (
      russianDiacriticalRegex.test(char) ||
      englishDiacriticalRegex.test(char)
    ) {
      if (previousWasDiacritical || !hasLetter) {
        return false;
      }

      if (russianDiacriticalRegex.test(char)) {
        const prevChar = text[i - 1];
        const russianVowels = "аеёиоуыэюяАЕЁИОУЫЭЮЯ";
        if (!russianVowels.includes(prevChar)) {
          return false;
        }
      }

      hasLetter = false;
      previousWasDiacritical = true;
      continue;
    }

    hasLetter = false;
    if (i >= 0 && (validSpecialChars.has(char) || /\s/.test(char))) {
      previousWasDiacritical = false;
      continue;
    }

    return false;
  }

  return true;
};

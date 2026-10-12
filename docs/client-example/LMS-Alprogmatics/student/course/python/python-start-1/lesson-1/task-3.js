// Python Start 1 - Lesson 1 - Sub-chapter 3 (TEMPLATE: mixed multiple choice + code practice).
// Each question picks its own type:
//   multiple choice -> { q, options: [...], answer: index | [indexes] }
//   code practice   -> { q, code: { starter, expected, input?, title?, goal? } }
// The screens are drawn by engine.js (level folder: python-start-1\engine.js); this file only holds the content.
PythonCourse.task("python-start-1", 1, 3, {
  title: "Mixed Practice", // name shown under the circle in the popup
  questions: [
    { q: "Which symbol starts a comment in Python?", options: ["//", "#", "--", "/*"], answer: 1 },
    {
      q: "Print the word Python and then the result of 4 * 5, each on its own line.",
      code: {
        starter: '# write your code below\n',
        expected: "Python\n20",
        goal: "The program prints Python and 20",
      },
    },
    { q: "What does print(3 + 4) show?", options: ["3 + 4", "7", "34", "Error"], answer: 1 },
    {
      q: "Fix the program so it prints Hello, World!",
      code: {
        starter: 'Print("Hello, World!")',
        expected: "Hello, World!",
      },
    },
  ],
});
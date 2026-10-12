// Python Start 1 - Lesson 1 - Sub-chapter 2.
// Code practice question: { q, code: { starter, expected, input? } }
// The student's program is run and its output must equal `expected` (trailing spaces/blank edges ignored).
PythonCourse.task("python-start-1", 1, 2, {
  title: "Code Practice", // name shown under the circle in the popup
  questions: [
    {
      q: "Fix the syntax error so the program prints the text below.",
      code: {
        starter: 'print("Python was created by Guido van Rossum"\nprint("First released in 1991")',
        expected: "Python was created by Guido van Rossum\nFirst released in 1991",
      },
    },
    {
      q: "Write a program that prints your name on the first line and the result of 2 + 3 on the second line. Use the name Alex.",
      code: {
        starter: "# write your code here\n",
        expected: "Alex\n5",
      },
    },
    {
      q: "The program below has two mistakes. Fix them so it prints a short greeting card.",
      code: {
        title: "Greeting card",
        goal: "The program prints the greeting card",
        starter: 'Print("*** Greeting Card ***")\nprint("Hello, Python!"\nprint("*** Enjoy coding ***")',
        expected: "*** Greeting Card ***\nHello, Python!\n*** Enjoy coding ***",
      },
    },
    {
      q: "Print the result of each calculation on its own line: 12 + 8, 12 - 8, 12 * 8 and 12 / 4. Use print() with the expression inside.",
      code: {
        title: "Calculator",
        goal: "The program prints four results",
        starter: "# print 12 + 8, 12 - 8, 12 * 8 and 12 / 4\n",
        expected: "20\n4\n96\n3.0",
      },
    },
    {
      q: "The program reads a name with input() (the typed name shows up in the console first). Print the text Hello, followed by a comma, a space and the name, then print the length of the name using len().",
      code: {
        title: "Meet the user",
        goal: "The program greets the user and prints the name length",
        starter: 'name = input()\n# print "Hello, <name>!" and then len(name)\n',
        input: ["Python"],
        expected: "Python\nHello, Python!\n6",
      },
    },  ],
});
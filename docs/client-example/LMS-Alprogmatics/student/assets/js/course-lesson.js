const lessonCatalog = {
  python: { name: 'Python', icon: 'Py', meta: '4 Levels • 128 Lessons', lessons: [{ title: 'Python Star 1', body: 'Learn how to write simple Python instructions and run your very first program.', heading: 'Print and comments', text: 'Use print() to show text. Comments start with a hash sign and are ignored by Python.', code: '# My first program\nprint("Hello, world!")' }, { title: 'Python Star 2', body: 'Store information in variables, then use it again in your Python program.', heading: 'Creating variables', text: 'Variables store values with an equals sign. Python figures out the data type automatically.', code: 'name = "Ardi"\nage = 16\nprint(name, age)' }, { title: 'Python Pro 1', body: 'Use if and else so your program can make decisions.', heading: 'if and else logic', text: 'Control your program with comparisons and four-space indentation.', code: 'score = 85\nif score >= 75:\n    print("Pass")\nelse:\n    print("Try again")' }, { title: 'Python Pro 2', body: 'Mix functions, lists, and loops to build stronger programs.', heading: 'Making reusable functions', text: 'Break your program into small functions that are easy to test and reuse.', code: 'def calculate_area(length, width):\n    return length * width\n\nprint(calculate_area(8, 5))' }] },
  data: { name: 'Data', icon: '∿', meta: '10 Lessons • Intermediate', lessons: [{ title: 'Data Explorer 1', body: 'Meet data, learn how to group it, and see how it helps people make decisions.', heading: 'Start learning data', text: 'Learn how to read, clean, and present data.', code: 'data = [12, 18, 15, 20]\naverage = sum(data) / len(data)\nprint(average)' }, { title: 'Data Explorer 2', body: 'Organize information in a table so it is easy to compare.', heading: 'Reading a table', text: 'Use rows and columns to spot patterns in data.', code: 'products = {"books": 12, "pens": 20}\nprint(products)' }] },
  ai: { name: 'Artificial Intelligence', icon: 'AI', meta: '14 Lessons • Beginner', lessons: [{ title: 'Jr AI', body: 'Discover artificial intelligence and examples of how it is used around us every day.', heading: 'What is AI?', text: 'Artificial Intelligence helps computers do tasks that usually need human thinking.', code: 'ai_examples = ["recommendations", "voice recognition"]\nprint(ai_examples)' }, { title: 'Sr AI', body: 'Understand how data, patterns, and predictions connect.', heading: 'AI learns from data', text: 'Good data helps a system notice patterns more accurately.', code: 'data = [1, 2, 3, 4]\nprint("Pattern found")' }] },
  scratch: { name: 'Scratch', icon: '★', meta: '8 Lessons • Beginner', lessons: [{ title: 'Scratch Creator 1', body: 'Learn coding with visual blocks and make fun games.', heading: 'Your first blocks', text: 'Combine event and motion blocks to make your character move.', code: 'when green flag clicked\nmove 10 steps' }, { title: 'Scratch Creator 2', body: 'Create character movement and sounds.', heading: 'Making animation', text: 'Use loops to keep movement smooth and consistent.', code: 'repeat 10\n    move 10 steps' }] },
  'komputer-jaringan': { name: 'Computer & Networks', icon: '⌁', meta: '16 Lessons • Beginner', lessons: [{ title: 'Network Starter', body: 'Understand the main computer parts and what each device does.', heading: 'Meet computer devices', text: 'Learn the difference between hardware, software, input, process, and output.', code: 'input -> process -> output' }, { title: 'Network Builder', body: 'Learn about operating systems and how computers manage programs.', heading: 'Operating systems', text: 'An operating system connects the user with the hardware.', code: 'user -> operating system -> hardware' }] },
  'web-desktop': { name: 'Web Development', icon: '</>', meta: '18 Lessons • Beginner', lessons: [{ title: 'Web Builder 1', body: 'Build the structure of a web page with semantic HTML.', heading: 'Page structure', text: 'Start with header, main, section, and footer elements.', code: '<main>\n  <h1>Hello Web</h1>\n</main>' }, { title: 'Web Builder 2', body: 'Adjust colors, spacing, layout, and the look of the page.', heading: 'Page styling', text: 'Use CSS to make your page tidier and easier to read.', code: 'body {\n  color: #263b47;\n}' }] },
  'application-desktop': { name: 'Desktop Applications', icon: '▣', meta: '15 Lessons • Intermediate', lessons: [{ title: 'Desktop Maker 1', body: 'Learn the structure and workflow of a desktop application.', heading: 'Starting a desktop app', text: 'Meet windows, menus, forms, buttons, and events.', code: 'window = create_window("App")\nwindow.show()' }, { title: 'Desktop Maker 2', body: 'Design a desktop app interface that is easy to use.', heading: 'Building an interface', text: 'Arrange components so the app flow is easy for users to follow.', code: 'button = Button("Save")\nbutton.show()' }] },
  android: { name: 'Android', icon: '◆', meta: '20 Lessons • Intermediate', lessons: [{ title: 'Android Starter 1', body: 'Learn the structure of an Android app and its main parts.', heading: 'Your first mobile app', text: 'Explore screens, layouts, components, and the flow of an Android app.', code: 'Text("Hello Android")' }, { title: 'Android Starter 2', body: 'Arrange app components in a responsive layout.', heading: 'Building layouts', text: 'Use containers and constraints to place components neatly.', code: 'Column {\n    Text("Home")\n}' }] },
  iot: { name: 'Internet of Things', icon: '⌁', meta: '17 Lessons • Intermediate', lessons: [{ title: 'IoT Maker 1', body: 'Discover the Internet of Things and how devices connect to each other.', heading: 'Smart devices', text: 'Learn how sensors, microcontrollers, networks, and data work together.', code: 'sensor.read()\nserver.send(data)' }, { title: 'IoT Maker 2', body: 'Use sensors to read what is happening in the environment.', heading: 'Reading sensors', text: 'Sensors turn physical conditions into data that can be processed.', code: 'temperature = sensor.read()\nprint(temperature)' }] }
};

const courseKey = document.body.dataset.course;
const course = lessonCatalog[courseKey];
const lessonPageNames = { data: 'data-explorer.html', ai: 'jr-ai.html', scratch: 'scratch-creator.html', 'komputer-jaringan': 'network-starter.html', 'web-desktop': 'web-builder.html', 'application-desktop': 'desktop-maker.html', android: 'android-starter.html', iot: 'iot-maker.html' };
const lessonPage = lessonPageNames[courseKey];
const selectedLesson = Math.max(0, Number(new URLSearchParams(window.location.search).get('lesson') || 0));
const lesson = course.lessons[selectedLesson] || course.lessons[0];
document.title = `${course.name} | Alprogmatics`;
const courseName = document.getElementById('courseName');
const courseMeta = document.getElementById('courseMeta');
const courseIcon = document.getElementById('courseIcon');
const selectedLessonTitle = document.getElementById('selectedLessonTitle');
const selectedLessonBody = document.getElementById('selectedLessonBody');
const selectedLessonHeading = document.getElementById('selectedLessonHeading');
const selectedLessonText = document.getElementById('selectedLessonText');
const selectedLessonCode = document.getElementById('selectedLessonCode');
if (courseName) courseName.textContent = course.name;
if (courseMeta) courseMeta.textContent = course.meta;
if (courseIcon) courseIcon.textContent = course.icon;
if (selectedLessonTitle) selectedLessonTitle.textContent = lesson.title;
if (selectedLessonBody) selectedLessonBody.textContent = lesson.body;
if (selectedLessonHeading) selectedLessonHeading.textContent = lesson.heading;
if (selectedLessonText) selectedLessonText.textContent = lesson.text;
if (selectedLessonCode) selectedLessonCode.textContent = lesson.code;
const scratchQuestionTitle = document.getElementById('scratch-question-title');
const scratchQuestionBody = document.getElementById('scratch-question-body');
const scratchQuestionText = document.getElementById('scratch-question-text');
const scratchQuestionCode = document.getElementById('scratch-question-code');
if (scratchQuestionTitle) scratchQuestionTitle.textContent = lesson.heading;
if (scratchQuestionBody) scratchQuestionBody.textContent = lesson.body;
if (scratchQuestionText) scratchQuestionText.textContent = lesson.text;
if (scratchQuestionCode) scratchQuestionCode.textContent = lesson.code;
const lessonCount = document.getElementById('lessonCount');
if (lessonCount) lessonCount.textContent = `Lesson ${selectedLesson + 1} of ${course.meta.split(' ')[0]}`;
const scratchWorkspaceLink = document.getElementById('scratchWorkspaceLink');
if (scratchWorkspaceLink) scratchWorkspaceLink.href = `workspace.html?lesson=${selectedLesson}`;
const backToLesson = document.getElementById('backToLesson');
if (backToLesson) backToLesson.href = `scratch-creator.html?lesson=${selectedLesson}`;

function toggleScratchQuestions() {
  const drawer = document.getElementById('scratchQuestionDrawer');
  const toggle = document.getElementById('scratchQuestionToggle');
  if (!drawer || !toggle) return;
  const isOpen = drawer.classList.toggle('open');
  toggle.classList.toggle('open', isOpen);
  toggle.setAttribute('aria-expanded', String(isOpen));
  toggle.textContent = isOpen ? 'Close' : 'Questions';
}

const initialQuestionDrawer = document.getElementById('scratchQuestionDrawer');
const initialQuestionToggle = document.getElementById('scratchQuestionToggle');
if (initialQuestionDrawer?.classList.contains('open') && initialQuestionToggle) {
  initialQuestionToggle.textContent = 'Close';
}

function openScratchWorkspace() {
  const workspace = document.getElementById('scratch-workspace');
  const drawer = document.getElementById('scratchQuestionDrawer');
  const toggle = document.getElementById('scratchQuestionToggle');
  if (workspace) workspace.scrollIntoView({ behavior: 'smooth', block: 'start' });
  if (drawer && toggle && !drawer.classList.contains('open')) {
    toggleScratchQuestions();
  }
}

const lessonNav = document.getElementById('lessonNav');
if (lessonNav) {
  lessonNav.innerHTML = course.lessons.map((item, index) => `<a class="${index === selectedLesson ? 'active' : ''}" href="${lessonPage}?lesson=${index}"><span class="lesson-state">${index === selectedLesson ? '▶' : String(index + 1).padStart(2, '0')}</span>${item.title}</a>`).join('');
}
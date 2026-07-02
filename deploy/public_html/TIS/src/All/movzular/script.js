
        let currentExamTab = "examInfo";
        const quillEditors = {};

        document.addEventListener("DOMContentLoaded", () => {
            const sidebarToggle = document.getElementById("sidebarToggle");
            const sidebar = document.getElementById("sidebar");
            const mainContent = document.getElementById("mainContent");

            if (sidebarToggle) {
                sidebarToggle.addEventListener("click", () => {
                    sidebar.classList.toggle("sidebar-collapsed");
                    mainContent.classList.toggle("main-content-expanded");
                });
            }

            const sidebarMenuLinks = document.querySelectorAll(".sidebar-menu-link");
            let activeSection = localStorage.getItem("activeSection") || "dashboard";

            // Set initial active section
            showSection(activeSection);
            sidebarMenuLinks.forEach(link => {
                if (link.getAttribute("data-section") === activeSection) {
                    link.classList.add("active");
                }
            });

            sidebarMenuLinks.forEach((link) => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();

                    sidebarMenuLinks.forEach((l) => l.classList.remove("active"));
                    this.classList.add("active");

                    const sectionId = this.getAttribute("data-section");
                    showSection(sectionId);
                    localStorage.setItem("activeSection", sectionId);
                });
            });

            initializeFileUploads();
            initializeExamTabs();
            initializeQuillEditors();
            updateQuestionTopics();
            updateExamTopics();
            loadAttendance();
        });

        function showSection(sectionId) {
            const sections = document.querySelectorAll(".section");
            sections.forEach((section) => {
                section.classList.remove("active");
            });

            const selectedSection = document.getElementById(sectionId);
            if (selectedSection) {
                selectedSection.classList.add("active");

                if (sectionId === "exams" || sectionId === "assignments" || sectionId === "question-bank") {
                    setTimeout(() => {
                        reinitializeQuillEditors();
                    }, 100);
                }
            }

            const sidebarMenuLinks = document.querySelectorAll(".sidebar-menu-link");
            sidebarMenuLinks.forEach((link) => {
                link.classList.remove("active");
                if (link.getAttribute("data-section") === sectionId) {
                    link.classList.add("active");
                }
            });
        }

        function initializeExamTabs() {
            const tabs = document.querySelectorAll("#examTabs .tab");
            tabs.forEach((tab) => {
                tab.addEventListener("click", function () {
                    const tabId = this.getAttribute("data-tab");
                    switchExamTab(tabId);
                });
            });
        }

        function switchExamTab(tabId) {
            const tabs = document.querySelectorAll("#examTabs .tab");
            tabs.forEach((tab) => {
                tab.classList.remove("active");
                if (tab.getAttribute("data-tab") === tabId) {
                    tab.classList.add("active");
                }
            });

            const contents = document.querySelectorAll(".tab-content");
            contents.forEach((content) => {
                content.classList.remove("active");
                if (content.id === tabId) {
                    content.classList.add("active");
                }
            });

            currentExamTab = tabId;
            updateExamTabButtons();

            if (tabId === "examInfo") {
                setTimeout(() => {
                    reinitializeQuillEditors();
                }, 100);
            }
        }

        function nextExamTab() {
            if (currentExamTab === "examInfo") {
                switchExamTab("examQuestions");
            } else if (currentExamTab === "examQuestions") {
                switchExamTab("examGroups");
            }
        }

        function prevExamTab() {
            if (currentExamTab === "examGroups") {
                switchExamTab("examQuestions");
            } else if (currentExamTab === "examQuestions") {
                switchExamTab("examInfo");
            }
        }

        function updateExamTabButtons() {
            const prevBtn = document.getElementById("prevTabBtn");
            const nextBtn = document.getElementById("nextTabBtn");
            const saveBtn = document.getElementById("saveExamBtn");

            if (currentExamTab === "examInfo") {
                prevBtn.style.display = "none";
                nextBtn.style.display = "block";
                saveBtn.style.display = "none";
            } else if (currentExamTab === "examQuestions") {
                prevBtn.style.display = "block";
                nextBtn.style.display = "block";
                saveBtn.style.display = "none";
            } else if (currentExamTab === "examGroups") {
                prevBtn.style.display = "block";
                nextBtn.style.display = "none";
                saveBtn.style.display = "block";
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove("show");
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("show");
        }

        function initializeFileUploads() {
            console.log("File uploads initialized");
        }

        function updateQuestionTopics() {
            console.log("Question topics updated");
        }

        function updateExamTopics() {
            console.log("Exam topics updated");
        }

        function loadAttendance() {
            console.log("Attendance loaded");
        }

        function initializeQuillEditors() {
            if (typeof Quill === "undefined") {
                console.error("Quill library is not loaded");
                return;
            }

            function initializeQuill(elementId, editorKey) {
                const editorElement = document.getElementById(elementId);
                if (!editorElement) {
                    console.warn(`Editor element with ID ${elementId} not found`);
                    return;
                }

                if (quillEditors[editorKey]) {
                    return;
                }

                try {
                    quillEditors[editorKey] = new Quill(`#${elementId}`, {
                        theme: "snow",
                        modules: {
                            toolbar: [
                                ["bold", "italic", "underline", "strike"],
                                ["blockquote", "code-block"],
                                [{ header: 1 }, { header: 2 }],
                                [{ list: "ordered" }, { list: "bullet" }],
                                [{ script: "sub" }, { script: "super" }],
                                [{ indent: "-1" }, { indent: "+1" }],
                                [{ direction: "rtl" }],
                                [{ size: ["small", false, "large", "huge"] }],
                                [{ color: [] }, { background: [] }],
                                [{ font: [] }],
                                [{ align: [] }],
                                ["clean"],
                                ["link", "image"],
                            ],
                        },
                    });
                    console.log(`Quill editor initialized for ${elementId}`);
                } catch (error) {
                    console.error(`Error initializing Quill editor for ${elementId}:`, error);
                }
            }

            initializeQuill("assignmentDescriptionEditor", "assignmentDescription");
            initializeQuill("examDescriptionEditor", "examDescription");
            initializeQuill("questionTextEditor", "questionText");
        }

        function reinitializeQuillEditors() {
            destroyQuillEditors();
            initializeQuillEditors();
        }

        function destroyQuillEditors() {
            const editorIds = ["assignmentDescriptionEditor", "examDescriptionEditor", "questionTextEditor"];
            editorIds.forEach((id) => {
                const editorElement = document.getElementById(id);
                if (!editorElement) return;

                const container = editorElement.parentElement;
                const toolbars = container.querySelectorAll(".ql-toolbar.ql-snow");
                toolbars.forEach((toolbar) => toolbar.remove());

                editorElement.innerHTML = "";

                const editorKey = id.replace("Editor", "");
                quillEditors[editorKey] = null;
            });
        }
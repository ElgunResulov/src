let Quill;
let optionEditors = {};
let subjectsCache = null; // Cache for subjects to prevent duplicate fetching
let topicsCache = {}; // Cache for topics to prevent duplicate fetching

document.addEventListener("DOMContentLoaded", () => {
if (window.Quill) {
  Quill = window.Quill;
}

const openModalBtn = document.getElementById("openModalBtn");
if (openModalBtn) {
  openModalBtn.addEventListener("click", createAndShowModal);
}

const filterBtn = document.getElementById("filterQuestionsBtn");
if (filterBtn) {
  filterBtn.addEventListener("click", filterQuestions);
}
});

function isBase64Image(str) {
return typeof str === 'string' && str.startsWith('data:image/');
}

function compressImage(base64Image, maxWidth = 1200, maxHeight = 1200, quality = 0.8) {
return new Promise((resolve, reject) => {
  const img = new Image();
  img.onload = function() {
    let width = img.width;
    let height = img.height;
    
    if (width > maxWidth) {
      height = Math.round(height * (maxWidth / width));
      width = maxWidth;
    }
    if (height > maxHeight) {
      width = Math.round(width * (maxHeight / height));
      height = maxHeight;
    }
    
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, width, height);
    const compressedImage = canvas.toDataURL('image/jpeg', quality);
    resolve(compressedImage);
  };
  img.onerror = function() {
    reject(new Error('Failed to load image'));
  };
  img.src = base64Image;
});
}

function handleQuestionFormSubmit(form, closeModalFunction) {
const questionType = form.querySelector("#questionType").value;
const subject = form.querySelector("#questionSubject").value;
const topic = form.querySelector("#questionTopic").value;
const difficulty = form.querySelector("#questionDifficulty").value;
const hiddenTextInput = form.querySelector("#questionText");
const questionText = hiddenTextInput ? hiddenTextInput.value : '';
const questionImageInput = form.querySelector("#questionImageHidden");
const questionImage = questionImageInput ? questionImageInput.value : '';
if (!subject || !topic || !difficulty) {
  alert("Zəhmət olmasa bütün vacib sahələri doldurun!");
  return;
}

if (!questionText && !questionImage) {
  alert("Zəhmət olmasa sual mətni və ya şəkli daxil edin!");
  return;
}

const questionData = {
  subject: subject,
  topic: topic,
  question_type: questionType,
  question_text: questionText,
  question_image: questionImage,
  difficulty: difficulty,
};

if (questionType === "multiple_choice") {
  const correctOptions = form.querySelectorAll("input[name='correctOptions']:checked");
  if (correctOptions.length === 0) {
    let errorDiv = form.querySelector("#multipleChoiceOptions .error-message");
    if (!errorDiv) {
      errorDiv = document.createElement("div");
      errorDiv.className = "error-message";
      form.querySelector("#multipleChoiceOptions").appendChild(errorDiv);
    }
    errorDiv.textContent = "Ən azı bir doğru cavab seçilməlidir!";
    return;
  }

  const options = Array.from(form.querySelectorAll(".option-item")).map((item, index) => {
    const editorId = item.querySelector(".option-editor").id;
    const editor = optionEditors[editorId];
    return {
      text: editor.root.innerHTML,
      isCorrect: item.querySelector("input[name='correctOptions']").checked,
    };
  });

  questionData.options = options;
} else if (questionType === "open_ended") {
  questionData.correct_answer = form.querySelector("#openAnswer").value;
} else if (questionType === "true_false") {
  questionData.correct_answer = form.querySelector("input[name='trueFalseAnswer']:checked").value;
} else if (questionType === "matching") {
  const pairs = Array.from(form.querySelectorAll(".pair-item")).map((item) => {
    return {
      left: item.querySelector(".pair-left").value,
      right: item.querySelector(".pair-right").value,
    };
  });

  questionData.pairs = pairs;
}

saveQuestion(questionData, form, closeModalFunction);
}

function saveQuestion(questionData, form, closeModalFunction) {
const submitButton = form.querySelector("button[type='submit']");
const originalText = submitButton.textContent;
submitButton.textContent = "Yüklənir...";
submitButton.disabled = true;

const processAndSend = async () => {
  try {
    if (questionData.question_image && isBase64Image(questionData.question_image)) {
      questionData.question_image = await compressImage(questionData.question_image);
    }
    
    const response = await fetch("movzular/suallar/operations.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(questionData),
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert("Sual uğurla yadda saxlanıldı!");
      if (closeModalFunction) closeModalFunction();
      window.location.reload();
    } else {
      alert("Xəta: " + (result.error || "Bilinməyən xəta baş verdi"));
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.");
  } finally {
    submitButton.textContent = originalText;
    submitButton.disabled = false;
  }
};

processAndSend();
}

function toggleQuestionType(modal) {
const questionType = modal.querySelector("#questionType").value;
const multipleChoice = modal.querySelector("#multipleChoiceOptions");
const openEnded = modal.querySelector("#openEndedAnswer");
const trueFalse = modal.querySelector("#trueFalseAnswer");
const matching = modal.querySelector("#matchingPairs");
multipleChoice.style.display = questionType === "multiple_choice" ? "block" : "none";
openEnded.style.display = questionType === "open_ended" ? "block" : "none";
trueFalse.style.display = questionType === "true_false" ? "block" : "none";
matching.style.display = questionType === "matching" ? "block" : "none";
openEnded.querySelector("textarea").required = questionType === "open_ended";
matching.querySelectorAll("input[type='text']").forEach((input) => {
  input.required = questionType === "matching";
});

const errorDiv = modal.querySelector("#multipleChoiceOptions .error-message");
if (errorDiv) errorDiv.remove();
}

function handleFileInputChange(fileInput, previewElement, hiddenInput) {
fileInput.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  
  if (!file.type.match('image.*')) {
    alert('Zəhmət olmasa yalnız şəkil faylları yükləyin!');
    return;
  }
  
  if (file.size > 5 * 1024 * 1024) {
    alert('Şəkil həcmi 5MB-dan çox olmamalıdır!');
    return;
  }
  
  const reader = new FileReader();
  reader.onload = function(e) {
    const imageData = e.target.result;
    previewElement.innerHTML = `<img src="${imageData}" alt="Preview" style="max-width: 100%; max-height: 200px;">`;
    hiddenInput.value = imageData;
  };
  reader.readAsDataURL(file);
});
}

function initializeEventListeners(modal) {
modal.querySelectorAll(".add-option").forEach((btn) => {
  btn.addEventListener("click", () => {
    const optionsList = modal.querySelector("#optionsList");
    const optionCount = optionsList.querySelectorAll(".option-item").length;
    const newOption = document.createElement("div");
    newOption.className = "form-group option-item";
    const editorId = `option-editor-${optionCount}`;

    newOption.innerHTML = `
      <div class="option-row">
        <input type="checkbox" name="correctOptions" value="${optionCount}" class="option-checkbox">
        <div id="${editorId}" class="option-editor quill-container"></div>
        <button type="button" class="btn btn-sm btn-danger remove-option option-remove"><i class="fas fa-times"></i></button>
      </div>
    `;

    optionsList.appendChild(newOption);

    optionEditors[editorId] = new Quill(`#${editorId}`, {
      theme: "snow",
      modules: {
        toolbar: [["bold", "italic", "underline"], ["image"]],
      },
      placeholder: `Seçim ${optionCount + 1}`,
    });

    const toolbar = newOption.querySelector(".ql-toolbar");
    const container = newOption.querySelector(".ql-container");
    if (toolbar && container) {
      newOption.querySelector(".option-editor").appendChild(toolbar);
    }

    newOption.querySelector(".remove-option").addEventListener("click", () => {
      if (modal.querySelectorAll(".option-item").length > 2) {
        delete optionEditors[editorId];
        newOption.remove();
        modal.querySelectorAll(".option-item").forEach((item, index) => {
          item.querySelector("input[name='correctOptions']").value = index;
        });
      }
    });
  });
});

modal.querySelectorAll(".remove-option").forEach((btn) => {
  btn.addEventListener("click", () => {
    const element = btn.closest(".option-item");
    if (modal.querySelectorAll(".option-item").length > 2) {
      const editorId = element.querySelector(".option-editor").id;
      delete optionEditors[editorId];
      element.remove();

      modal.querySelectorAll(".option-item").forEach((item, index) => {
        item.querySelector("input[name='correctOptions']").value = index;
      });
    }
  });
});

modal.querySelectorAll(".add-pair").forEach((btn) => {
  btn.addEventListener("click", () => {
    const pairsList = modal.querySelector("#pairsList");
    const newPair = document.createElement("div");
    newPair.className = "form-group pair-item";
    newPair.innerHTML = `
      <div class="pair-row">
        <input type="text" class="pair-left" placeholder="Sol tərəf" required>
        <i class="fas fa-arrow-right"></i>
        <input type="text" class="pair-right" placeholder="Sağ tərəf" required>
        <button type="button" class="btn btn-sm btn-danger remove-pair"><i class="fas fa-times"></i></button>
      </div>
    `;
    pairsList.appendChild(newPair);
    newPair.querySelector(".remove-pair").addEventListener("click", () => {
      if (modal.querySelectorAll(".pair-item").length > 2) {
        newPair.remove();
      }
    });
  });
});

modal.querySelectorAll(".remove-pair").forEach((btn) => {
  btn.addEventListener("click", () => {
    const element = btn.closest(".pair-item");
    if (modal.querySelectorAll(".pair-item").length > 2) {
      element.remove();
    }
  });
});

updatePreviewHandler(modal);
}

function updatePreviewHandler(modal) {
const previewBtn = modal.querySelector(".preview-btn");
if (previewBtn) {
  previewBtn.addEventListener("click", () => {
    const hiddenInput = modal.querySelector("#questionText");
    const questionType = modal.querySelector("#questionType").value;
    const previewContent = modal.querySelector("#previewContent");
    const previewContainer = modal.querySelector("#previewContainer");
    const questionText = hiddenInput ? hiddenInput.value : '';
    const questionImageInput = modal.querySelector("#questionImageHidden");
    const questionImage = questionImageInput ? questionImageInput.value : '';
    let previewHtml = '';
    if (questionText) {
      previewHtml += `<div class="question-text-content">${questionText}</div>`;
    }
    
    if (questionImage) {
      previewHtml += `<div class="question-image-content"><img src="${questionImage}" alt="Question Image" style="max-width: 100%;"></div>`;
    }

    if (questionType === "multiple_choice") {
      previewHtml += `<div class="question-options"><h3>Seçimlər:</h3>`;
      
      const options = Array.from(modal.querySelectorAll(".option-item")).map((item, index) => {
        const isCorrect = item.querySelector("input[name='correctOptions']").checked;
        const editorId = item.querySelector(".option-editor").id;
        const optionText = optionEditors[editorId].root.innerHTML;
        const letters = ["A", "B", "C", "D", "E", "F", "G", "H"];
        const letter = index < letters.length ? letters[index] : index + 1;
        
        return `
          <div class="option-view ${isCorrect ? "option-correct" : ""}">
            <div class="option-letter">${letter}</div>
            <div class="option-text compact-option">${optionText}</div>
            ${isCorrect ? '<div class="option-status"><i class="fas fa-check-circle"></i> Doğru</div>' : ""}
          </div>
        `;
      });
      
      previewHtml += options.join("") + `</div>`;
    } else if (questionType === "open_ended") {
      previewHtml += `<div><strong>Doğru Cavab:</strong> ${modal.querySelector("#openAnswer").value || "Yoxdur"}</div>`;
    } else if (questionType === "true_false") {
      const checked = modal.querySelector("input[name='trueFalseAnswer']:checked");
      previewHtml += `<div><strong>Doğru Cavab:</strong> ${checked ? (checked.value === "true" ? "Doğru" : "Yanlış") : "Seçilməyib"}</div>`;
    } else if (questionType === "matching") {
      const pairs = Array.from(modal.querySelectorAll(".pair-item")).map((item) => {
        return `<div>${item.querySelector(".pair-left").value} → ${item.querySelector(".pair-right").value}</div>`;
      });
      previewHtml += `<div><strong>Cütlər:</strong><br>${pairs.join("")}</div>`;
    }

    previewHtml += `<div><strong>Çətinlik:</strong> ${modal.querySelector("#questionDifficulty option:checked").text}</div>`;
    previewContent.innerHTML = previewHtml;
    previewContainer.style.display = "block";
    modal.querySelector(".simple-modal-content").scrollTop = modal.querySelector(".simple-modal-content").scrollHeight;
  });
}

const closePreviewBtn = modal.querySelector(".close-preview");
if (closePreviewBtn) {
  closePreviewBtn.addEventListener("click", () => {
    modal.querySelector("#previewContainer").style.display = "none";
  });
}
}

function fetchSubjects(modal) {
const subjectSelect = modal.querySelector("#questionSubject");
if (subjectsCache !== null) {
  subjectSelect.innerHTML = '<option value="">Seçin</option>';
  subjectsCache.forEach((subject) => {
    const option = document.createElement("option");
    option.value = subject.ixtisas_adi;
    option.textContent = subject.ixtisas_adi;
    subjectSelect.appendChild(option);
  });
  return;
}

fetch("movzular/suallar/operations.php?action=get_subjects_topics")
  .then((response) => response.json())
  .then((data) => {
    console.log("Subjects response:", data);
    if (!data.success) {
      console.error("Error fetching subjects:", data.error);
      return;
    }
    
    subjectsCache = data.subjects;
    subjectSelect.innerHTML = '<option value="">Seçin</option>';
    data.subjects.forEach((subject) => {
      const option = document.createElement("option");
      option.value = subject.ixtisas_adi;
      option.textContent = subject.ixtisas_adi;
      subjectSelect.appendChild(option);
    });
  })
  .catch((error) => console.error("Fetch subjects error:", error));
}

function fetchTopics(modal, ixtisas_adi) {
const topicSelect = modal.querySelector("#questionTopic");
topicSelect.innerHTML = '<option value="">Seçin</option>';

if (!ixtisas_adi) {
  console.warn("No ixtisas_adi provided for fetching topics");
  return;
}

if (topicsCache[ixtisas_adi]) {
  if (topicsCache[ixtisas_adi].length === 0) {
    topicSelect.innerHTML = '<option value="">No Movzu found</option>';
  } else {
    topicsCache[ixtisas_adi].forEach((topic) => {
      const option = document.createElement("option");
      option.value = topic.id;
      option.textContent = topic.movzu_adi;
      topicSelect.appendChild(option);
    });
  }
  return;
}

console.log("Fetching topics for ixtisas_adi:", ixtisas_adi);
fetch(`movzular/suallar/operations.php?action=get_subjects_topics&ixtisas_adi=${encodeURIComponent(ixtisas_adi)}`)
  .then((response) => response.json())
  .then((data) => {
    console.log("Topics response:", data);
    if (!data.success) {
      console.error("Error fetching topics:", data.error);
      return;
    }
    
    topicsCache[ixtisas_adi] = data.topics;
    
    if (data.topics.length === 0) {
      console.warn("No topics found for ixtisas_adi:", ixtisas_adi);
      topicSelect.innerHTML = '<option value="">No Movzu found</option>';
    } else {
      data.topics.forEach((topic) => {
        const option = document.createElement("option");
        option.value = topic.id;
        option.textContent = topic.movzu_adi;
        topicSelect.appendChild(option);
      });
    }
  })
  .catch((error) => console.error("Fetch topics error:", error));
}

function setupModalEventListeners(modal) {
const subjectSelect = modal.querySelector("#questionSubject");
subjectSelect.addEventListener("change", (event) => {
  const ixtisas_adi = event.target.value;
  console.log("Subject selected with ixtisas_adi:", ixtisas_adi);
  if (ixtisas_adi && ixtisas_adi !== "Seçin") {
    fetchTopics(modal, ixtisas_adi);
  } else {
    const topicSelect = modal.querySelector("#questionTopic");
    topicSelect.innerHTML = '<option value="">Seçin</option>';
    console.log("No valid ixtisas_adi selected, clearing topics");
  }
});
}

function createAndShowModal() {
const modal = document.createElement("div");
modal.className = "simple-modal";
modal.id = "simpleModal";
modal.innerHTML = `
  <div class="simple-modal-content">
    <h3>Yeni Sual Əlavə Et</h3>
    <form id="newQuestionForm">
      <div class="form-group">
        <label for="questionSubject">Fənn:</label>
        <select id="questionSubject" class="form-select" name="subject" required>
          <option value="">Seçin</option>
        </select>
      </div>
      <div class="form-group">
        <label for="questionTopic">Mövzu:</label>
        <select class="form-select" id="questionTopic" name="topic" required>
          <option value="">Seçin</option>
        </select>
      </div>
      <div class="form-group">
        <label for="questionType">Sual Növü:</label>
        <select class="form-select" id="questionType" required>
          <option value="multiple_choice">Çoxseçimli</option>
          <option value="open_ended">Açıq</option>
          <option value="true_false">Doğru/Yanlış</option>
          <option value="matching">Uyğunlaşdırma</option>
        </select>
      </div>
      <div class="form-group">
        <label for="questionTextEditor">Sual Mətni:</label>
        <div id="questionTextEditor" style="min-height: 150px;"></div>
        <input type="hidden" id="questionText" name="question_text">
        <br>
        <label for="questionImage">Sual Şəkli:</label>
        <input type="file" id="questionImage" accept="image/*" class="form-control">
        <div id="imagePreview" class="mt-2"></div>
        <input type="hidden" id="questionImageHidden" name="question_image">
        <small class="form-text text-muted">Həm mətn, həm də şəkil əlavə edə bilərsiniz.</small>
      </div>
      <div id="multipleChoiceOptions" class="form-group">
        <label>Seçimlər:</label>
        <div id="optionsList">
          <div class="form-group option-item">
            <div class="option-row">
              <input type="checkbox" name="correctOptions" value="0" class="option-checkbox">
              <div id="option-editor-0" class="option-editor quill-container"></div>
              <button type="button" class="btn btn-sm btn-danger remove-option option-remove"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div class="form-group option-item">
            <div class="option-row">
              <input type="checkbox" name="correctOptions" value="1" class="option-checkbox">
              <div id="option-editor-1" class="option-editor quill-container"></div>
              <button type="button" class="btn btn-sm btn-danger remove-option option-remove"><i class="fas fa-times"></i></button>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-secondary add-option"><i class="fas fa-plus"></i> Seçim Əlavə Et</button>
      </div>
      <div id="openEndedAnswer" class="form-group" style="display: none;">
        <label for="openAnswer">Doğru Cavab:</label>
        <textarea id="openAnswer" name="openAnswer" rows="3" class="answer-text"></textarea>
      </div>
      <div id="trueFalseAnswer" class="form-group" style="display: none;">
        <label>Doğru Cavab:</label>
        <div class="answer-option">
          <input type="radio" id="answerTrue" name="trueFalseAnswer" value="true" checked>
          <label for="answerTrue">Doğru</label>
        </div>
        <div class="answer-option">
          <input type="radio" id="answerFalse" name="trueFalseAnswer" value="false">
          <label for="answerFalse">Yanlış</label>
        </div>
      </div>
      <div id="matchingPairs" class="form-group" style="display: none;">
        <label>Uyğunlaşdırma Cütləri:</label>
        <div id="pairsList">
          <div class="form-group pair-item">
            <div class="pair-row">
              <input type="text" class="pair-left" placeholder="Sol tərəf" required>
              <i class="fas fa-arrow-right"></i>
              <input type="text" class="pair-right" placeholder="Sağ tərəf" required>
              <button type="button" class="btn btn-sm btn-danger remove-pair"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div class="form-group pair-item">
            <div class="pair-row">
              <input type="text" class="pair-left" placeholder="Sol tərəf" required>
              <i class="fas fa-arrow-right"></i>
              <input type="text" class="pair-right" placeholder="Sağ tərəf" required>
              <button type="button" class="btn btn-sm btn-danger remove-pair"><i class="fas fa-times"></i></button>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-secondary add-pair"><i class="fas fa-plus"></i> Cüt Əlavə Et</button>
      </div>
      <div class="form-group">
        <label for="questionDifficulty">Çətinlik Səviyyəsi:</label>
        <select id="questionDifficulty" class="form-select" name="difficulty" required>
          <option value="1">Asan</option>
          <option value="2">Orta</option>
          <option value="3">Çətin</option>
        </select>
      </div>
      <div class="form-buttons">
        <button type="button" class="btn btn-info preview-btn">Önizləmə</button>
        <button type="button" class="btn btn-secondary cancel-btn">Ləğv Et</button>
        <button type="submit" class="btn btn-primary">Yadda Saxla</button>
      </div>
      <div id="previewContainer" class="preview-container" style="display: none;">
        <h3>Sual Önizləməsi</h3>
        <div id="previewContent"></div>
        <button type="button" class="btn btn-secondary close-preview">Bağla</button>
      </div>
    </form>
  </div>
`;

fetchSubjects(modal);
setupModalEventListeners(modal);
document.body.appendChild(modal);
const quill = new Quill("#questionTextEditor", {
  theme: "snow",
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, 4, 5, 6, false] }, "bold", "italic", "underline", "strike"],
      ["code", "blockquote"],
      [{ script: "sub" }, { script: "super" }, "link"],
      [{ list: "ordered" }, { list: "bullet" }, { indent: "-1" }, { indent: "+1" }],
      [{ font: [] }, { size: ["small", "normal", "large", "huge"] }],
      [{ align: ["", "center", "right", "justify"] }, { direction: "rtl" }],
      [{ color: [] }, { background: [] }],
    ],
    math: {
      katex: window.katex || {}, 
      delimiters: [
        { left: "$$", right: "$$", display: true },
        { left: "$", right: "$", display: false },
      ],
      throwOnError: false, 
    },
  },
  placeholder: "",
});

const hiddenInput = modal.querySelector("#questionText");
quill.on("text-change", () => {
  hiddenInput.value = quill.root.innerHTML;
});

optionEditors = {};
modal.querySelectorAll(".option-editor").forEach((editorElement, index) => {
  const editorId = editorElement.id;
  optionEditors[editorId] = new Quill(`#${editorId}`, {
    theme: "snow",
    modules: {
      toolbar: [["bold", "italic", "underline"],["image"]],
    },
    placeholder: `Seçim ${index + 1}`,
  });

  const toolbar = editorElement.querySelector(".ql-toolbar");
  const container = editorElement.querySelector(".ql-container");
  if (toolbar && container) {
    editorElement.appendChild(toolbar);
  }
});

const fileInput = modal.querySelector('#questionImage');
const previewElement = modal.querySelector('#imagePreview');
const hiddenImageInput = modal.querySelector('#questionImageHidden');

handleFileInputChange(fileInput, previewElement, hiddenImageInput);
modal.style.display = "block";
setTimeout(() => {
  modal.classList.add("fade-in");
}, 10);

const closeModal = () => {
  modal.classList.remove("fade-in");
  modal.classList.add("fade-out");
  setTimeout(() => {
    modal.style.display = "none";
    document.body.removeChild(modal);
  }, 300);
};

window.closeModal = closeModal;
const closeBtn = modal.querySelector(".simple-modal-close");
if (closeBtn) {
  closeBtn.addEventListener("click", closeModal);
}
const cancelBtn = modal.querySelector(".cancel-btn");
if (cancelBtn) {
  cancelBtn.addEventListener("click", closeModal);
}

modal.addEventListener("click", (event) => {
  if (event.target === modal) {
    closeModal();
  }
});

const questionTypeSelect = modal.querySelector("#questionType");
questionTypeSelect.addEventListener("change", () => toggleQuestionType(modal));
initializeEventListeners(modal);
const form = modal.querySelector("#newQuestionForm");
form.addEventListener("submit", (e) => {
  e.preventDefault();
  handleQuestionFormSubmit(form, closeModal);
});
toggleQuestionType(modal);
}

function filterQuestions() {
const subjectFilter = document.getElementById("questionSubjectFilter").value;
const topicFilter = document.getElementById("questionTopicFilter").value;
const typeFilter = document.getElementById("questionTypeFilter").value;
const difficultyFilter = document.getElementById("questionDifficultyFilter").value;
const searchInput = document.getElementById("questionSearchInput").value.toLowerCase();
const questions = document.querySelectorAll(".question-item");
questions.forEach((question) => {
  const subjectId = question.getAttribute("data-subject-id");
  const topicId = question.getAttribute("data-topic-id");
  const type = question.getAttribute("data-type");
  const difficulty = question.getAttribute("data-difficulty");
  const text = question.querySelector(".question-text").textContent.toLowerCase();
  const matchesSubject = !subjectFilter || subjectId === subjectFilter;
  const matchesTopic = !topicFilter || topicId === topicFilter;
  const matchesType = !typeFilter || type === typeFilter;
  const matchesDifficulty = !difficultyFilter || difficulty === difficultyFilter;
  const matchesSearch = !searchInput || text.includes(searchInput);
  if (matchesSubject && matchesTopic && matchesType && matchesDifficulty && matchesSearch) {
    question.style.display = "block";
  } else {
    question.style.display = "none";
  }
});
}

function viewQuestion(id) {
// Create view modal
const modal = document.createElement("div");
modal.className = "simple-modal";
modal.id = "viewQuestionModal";
modal.innerHTML = `
  <div class="simple-modal-content">
    <h3>Sual Məlumatları</h3>
    <div id="questionDetails">
      <div class="text-center">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p>Yüklənir...</p>
      </div>
    </div>
    <div class="form-buttons">
      <button type="button" class="btn btn-secondary close-view-btn">Bağla</button>
      <button type="button" class="btn btn-primary edit-btn">Redaktə Et</button>
    </div>
  </div>
`;

document.body.appendChild(modal);
modal.style.display = "block";
setTimeout(() => {
  modal.classList.add("fade-in");
}, 10);

const closeModal = () => {
  modal.classList.remove("fade-in");
  modal.classList.add("fade-out");
  setTimeout(() => {
    modal.style.display = "none";
    document.body.removeChild(modal);
  }, 300);
};

const closeViewBtn = modal.querySelector(".close-view-btn");
if (closeViewBtn) {
  closeViewBtn.addEventListener("click", closeModal);
}

const editBtn = modal.querySelector(".edit-btn");
if (editBtn) {
  editBtn.addEventListener("click", () => {
    closeModal();
    editQuestion(id);
  });
}

modal.addEventListener("click", (event) => {
  if (event.target === modal) {
    closeModal();
  }
});

fetch(`movzular/suallar/operations.php?action=get_question&id=${id}`)
  .then((response) => response.json())
  .then((data) => {
    if (data.error) {
      document.getElementById("questionDetails").innerHTML = `
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i> Xəta: ${data.error}
        </div>
      `;
      return;
    }

    const question = data.question;
    let questionTypeAz = "";
    switch (question.question_type) {
      case "multiple_choice":
        questionTypeAz = "Çoxseçimli";
        break;
      case "open_ended":
        questionTypeAz = "Açıq";
        break;
      case "true_false":
        questionTypeAz = "Doğru/Yanlış";
        break;
      case "matching":
        questionTypeAz = "Uyğunlaşdırma";
        break;
      default:
        questionTypeAz = "Digər";
        break;
    }

    let difficultyAz = "";
    switch (Number.parseInt(question.difficulty)) {
      case 1:
        difficultyAz = "Asan";
        break;
      case 2:
        difficultyAz = "Orta";
        break;
      case 3:
        difficultyAz = "Çətin";
        break;
      default:
        difficultyAz = "Naməlum";
        break;
    }

    let detailsHtml = `
      <div class="question-view">
        <div class="question-meta-grid">
          <div class="meta-item">
            <span class="meta-label"><i class="fas fa-book"></i> Fənn:</span>
            <span class="meta-value">${question.subject_name || ""}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label"><i class="fas fa-bookmark"></i> Mövzu:</span>
            <span class="meta-value">${question.topic_name || ""}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label"><i class="fas fa-question-circle"></i> Növ:</span>
            <span class="meta-value">${questionTypeAz}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label"><i class="fas fa-signal"></i> Çətinlik:</span>
            <span class="meta-value">${difficultyAz}</span>
          </div>
        </div>
        
        <div class="question-content">
          <h3>Sual Mətni:</h3>
    `;
    
    if (question.question_text) {
      detailsHtml += `<div class="question-text-content">${question.question_text}</div>`;
    }
    
    if (question.has_image) {
      detailsHtml += `<div class="question-image-content"><img src="${question.question_image}" alt="Question Image" style="max-width: 100%;"></div>`;
    }
    
    detailsHtml += `</div>`;
    if (question.question_type === "multiple_choice" && question.options) {
      let options = [];
      try {
        options = typeof question.options === "string" ? JSON.parse(question.options) : question.options;
      } catch (e) {
        console.error("Error parsing options:", e);
      }

      detailsHtml += `<div class="question-options"><h3>Seçimlər:</h3>`;
      options.forEach((option, index) => {
        const isCorrect = option.isCorrect;
        const letters = ["A", "B", "C", "D", "E", "F", "G", "H"];
        const letter = index < letters.length ? letters[index] : index + 1;
        detailsHtml += `
          <div class="option-view ${isCorrect ? "option-correct" : ""}">
            <div class="option-letter">${letter}</div>
            <div class="option-text compact-option">${option.text}</div>
            ${isCorrect ? '<div class="option-status"><i class="fas fa-check-circle"></i> Doğru</div>' : ""}
          </div>
        `;
      });
      detailsHtml += `</div>`;
    } else if (question.question_type === "open_ended" && question.correct_answer) {
      let answer = { answer: "" };
      try {
        answer =
          typeof question.correct_answer === "string" ? JSON.parse(question.correct_answer) : question.correct_answer;
      } catch (e) {
        console.error("Error parsing answer:", e);
      }
      detailsHtml += `
        <div class="question-answer">
          <h3>Doğru Cavab:</h3>
          <div class="answer-text">${answer.answer || ""}</div>
        </div>
      `;
    } else if (question.question_type === "true_false" && question.correct_answer) {
      let answer = { answer: "false" };
      try {
        answer =
          typeof question.correct_answer === "string" ? JSON.parse(question.correct_answer) : question.correct_answer;
      } catch (e) {
        console.error("Error parsing answer:", e);
      }
      detailsHtml += `
        <div class="question-answer">
          <h3>Doğru Cavab:</h3>
          <div class="answer-text">${answer.answer === "true" ? "Doğru" : "Yanlış"}</div>
        </div>
      `;
    } else if (question.question_type === "matching" && question.correct_answer) {
      let pairs = [];
      try {
        pairs =
          typeof question.correct_answer === "string" ? JSON.parse(question.correct_answer) : question.correct_answer;
      } catch (e) {
        console.error("Error parsing pairs:", e);
      }
      detailsHtml += `<div class="question-pairs"><h3>Uyğunlaşdırma Cütləri:</h3>`;
      pairs.forEach((pair, index) => {
        detailsHtml += `
          <div class="pair-view">
            <div class="pair-left">${pair.left}</div>
            <div class="pair-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="pair-right">${pair.right}</div>
          </div>
        `;
      });
      detailsHtml += `</div>`;
    }
    detailsHtml += `</div>`;
    document.getElementById("questionDetails").innerHTML = detailsHtml;
  })
  .catch((error) => {
    console.error("Error:", error);
    document.getElementById("questionDetails").innerHTML = `
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> Xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.
      </div>
    `;
  });
}

function deleteRecord(id) {
if (confirm("Bu sualı silmək istədiyinizə əminsiniz?")) {
  fetch(`movzular/suallar/operations.php?id=${id}`, { method: "DELETE" })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        alert("Sual silinərkən xəta baş verdi.");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Xəta: Sual silinə bilmədi.");
    });
}
}

function editQuestion(id) {
// Create modal container
const modal = document.createElement("div");
modal.className = "simple-modal";
modal.id = "editQuestionModal";
modal.innerHTML = `
  <div class="simple-modal-content">
    <h3>Sualı Redaktə Et</h3>
    <div id="loadingIndicator" style="text-align: center; padding: 20px;">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Yüklənir...</span>
      </div>
      <p>Məlumatlar yüklənir...</p>
    </div>
    <div id="errorMessage" style="display: none; color: red; padding: 10px; text-align: center;"></div>
    <form id="editQuestionForm" style="display: none;">
      <input type="hidden" id="questionId" name="id">
      <div class="form-group">
        <label for="questionSubject">Fənn:</label>
        <select id="questionSubject" class="form-select" name="subject" required>
          <option value="">Seçin</option>
        </select>
      </div>
      <div class="form-group">
        <label for="questionTopic">Mövzu:</label>
        <select class="form-select" id="questionTopic" name="topic" required>
          <option value="">Seçin</option>
        </select>
      </div>
      <div class="form-group">
        <label for="questionType">Sual Növü:</label>
        <select class="form-select" id="questionType" name="question_type" required>
          <option value="multiple_choice">Çoxseçimli</option>
          <option value="open_ended">Açıq</option>
          <option value="true_false">Doğru/Yanlış</option>
          <option value="matching">Uyğunlaşdırma</option>
        </select>
      </div>
      <div class="form-group">
        <label for="questionTextEditor">Sual Mətni:</label>
        <div id="questionTextEditor" style="min-height: 150px;"></div>
        <input type="hidden" id="questionText" name="question_text">
        <br>
        <label for="questionImage">Sual Şəkli:</label>
        <input type="file" id="questionImage" accept="image/*" class="form-control">
        <div id="imagePreview" class="mt-2"></div>
        <input type="hidden" id="questionImageHidden" name="question_image">
        <small class="form-text text-muted">Həm mətn, həm də şəkil əlavə edə bilərsiniz.</small>
      </div>
      <div id="multipleChoiceOptions" class="form-group" style="display: none;">
        <label>Seçimlər:</label>
        <div id="optionsList"></div>
        <button type="button" class="btn btn-sm btn-secondary add-option"><i class="fas fa-plus"></i> Seçim Əlavə Et</button>
      </div>
      <div id="openEndedAnswer" class="form-group" style="display: none;">
        <label for="openAnswer">Doğru Cavab:</label>
        <textarea id="openAnswer" name="openAnswer" rows="3" class="answer-text"></textarea>
      </div>
      <div id="trueFalseAnswer" class="form-group" style="display: none;">
        <label>Doğru Cavab:</label>
        <div class="answer-option">
          <input type="radio" id="answerTrue" name="trueFalseAnswer" value="true">
          <label for="answerTrue">Doğru</label>
        </div>
        <div class="answer-option">
          <input type="radio" id="answerFalse" name="trueFalseAnswer" value="false">
          <label for="answerFalse">Yanlış</label>
        </div>
      </div>
      <div id="matchingPairs" class="form-group" style="display: none;">
        <label>Uyğunlaşdırma Cütləri:</label>
        <div id="pairsList"></div>
        <button type="button" class="btn btn-sm btn-secondary add-pair"><i class="fas fa-plus"></i> Cüt Əlavə Et</button>
      </div>
      <div class="form-group">
        <label for="questionDifficulty">Çətinlik Səviyyəsi:</label>
        <select class="form-select" id="questionDifficulty" name="difficulty" required>
          <option value="">Seçin</option>
          <option value="1">Asan</option>
          <option value="2">Orta</option>
          <option value="3">Çətin</option>
        </select>
      </div>
      <div class="form-buttons">
        <button type="button" class="btn btn-info preview-btn">Önizləmə</button>
        <button type="button" class="btn btn-secondary cancel-btn">Ləğv Et</button>
        <button type="submit" class="btn btn-primary">Yadda Saxla</button>
      </div>
      <div id="previewContainer" class="preview-container" style="display: none;">
        <h3>Sual Önizləməsi</h3>
        <div id="previewContent"></div>
        <button type="button" class="btn btn-secondary close-preview">Bağla</button>
      </div>
    </form>
  </div>
`;

document.body.appendChild(modal);
const loadingIndicator = modal.querySelector("#loadingIndicator");
const errorMessage = modal.querySelector("#errorMessage");
const form = modal.querySelector("#editQuestionForm");

const quill = new Quill("#questionTextEditor", {
  theme: "snow",
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, 4, 5, 6, false] }, "bold", "italic", "underline", "strike"],
      ["code", "blockquote"],
      [{ script: "sub" }, { script: "super" }, "link"],
      [{ list: "ordered" }, { list: "bullet" }, { indent: "-1" }, { indent: "+1" }],
      [{ font: [] }, { size: ["small", "normal", "large", "huge"] }],
      [{ align: ["", "center", "right", "justify"] }, { direction: "rtl" }],
      [{ color: [] }, { background: [] }],
    ],
  },
});

const hiddenInput = modal.querySelector("#questionText");
quill.on("text-change", () => {
  hiddenInput.value = quill.root.innerHTML;
});

const fileInput = modal.querySelector('#questionImage');
const previewElement = modal.querySelector('#imagePreview');
const hiddenImageInput = modal.querySelector('#questionImageHidden');
handleFileInputChange(fileInput, previewElement, hiddenImageInput);
optionEditors = {};
function showError(message) {
  loadingIndicator.style.display = "none";
  errorMessage.style.display = "block";
  errorMessage.textContent = message;
}
function showForm() {
  loadingIndicator.style.display = "none";
  errorMessage.style.display = "none";
  form.style.display = "block";
}
const closeModal = () => {
  modal.classList.remove("fade-in");
  modal.classList.add("fade-out");
  setTimeout(() => {
    modal.style.display = "none";
    document.body.removeChild(modal);
  }, 300);
};
const cancelBtn = modal.querySelector(".cancel-btn");
if (cancelBtn) {
  cancelBtn.addEventListener("click", closeModal);
}
modal.addEventListener("click", (event) => {
  if (event.target === modal) {
    closeModal();
  }
});
const questionTypeSelect = modal.querySelector("#questionType");
questionTypeSelect.addEventListener("change", () => {
  const questionType = questionTypeSelect.value;
  const multipleChoice = modal.querySelector("#multipleChoiceOptions");
  const openEnded = modal.querySelector("#openEndedAnswer");
  const trueFalse = modal.querySelector("#trueFalseAnswer");
  const matching = modal.querySelector("#matchingPairs");
  multipleChoice.style.display = questionType === "multiple_choice" ? "block" : "none";
  openEnded.style.display = questionType === "open_ended" ? "block" : "none";
  trueFalse.style.display = questionType === "true_false" ? "block" : "none";
  matching.style.display = questionType === "matching" ? "block" : "none";
  const openAnswerTextarea = openEnded.querySelector("textarea");
  if (openAnswerTextarea) {
    openAnswerTextarea.required = questionType === "open_ended";
  }

  const matchingInputs = matching.querySelectorAll("input[type='text']");
  if (matchingInputs) {
    matchingInputs.forEach((input) => {
      input.required = questionType === "matching";
    });
  }
});

fetchSubjects(modal);
const subjectSelect = modal.querySelector("#questionSubject");
subjectSelect.addEventListener("change", () => {
  const ixtisas_adi = subjectSelect.value;
  fetchTopics(modal, ixtisas_adi);
});

updatePreviewHandler(modal);
function setupOptionListeners() {
  modal.querySelector(".add-option").addEventListener("click", () => {
    const optionsList = modal.querySelector("#optionsList");
    const optionCount = optionsList.querySelectorAll(".option-item").length;
    const newOption = document.createElement("div");
    newOption.className = "form-group option-item";
    const editorId = `option-editor-edit-${optionCount}`;

    newOption.innerHTML = `
      <div class="option-row">
        <input type="checkbox" name="correctOptions" value="${optionCount}" class="option-checkbox">
        <div id="${editorId}" class="option-editor quill-container"></div>
        <button type="button" class="btn btn-sm btn-danger remove-option"><i class="fas fa-times"></i></button>
      </div>
    `;

    optionsList.appendChild(newOption);
    optionEditors[editorId] = new Quill(`#${editorId}`, {
      theme: "snow",
      modules: {
        toolbar: [["bold", "italic", "underline"], ["image"]],
      },
      placeholder: `Seçim ${optionCount + 1}`,
    });

    newOption.querySelector(".remove-option").addEventListener("click", () => {
      if (modal.querySelectorAll(".option-item").length > 2) {
        delete optionEditors[editorId];
        newOption.remove();
        modal.querySelectorAll(".option-item").forEach((item, idx) => {
          item.querySelector("input[name='correctOptions']").value = idx;
        });
      }
    });
  });
  
  modal.querySelector(".add-pair").addEventListener("click", () => {
    const pairsList = modal.querySelector("#pairsList");
    const newPair = document.createElement("div");
    newPair.className = "form-group pair-item";
    newPair.innerHTML = `
      <div class="pair-row">
        <input type="text" class="pair-left" placeholder="Sol tərəf" required>
        <i class="fas fa-arrow-right"></i>
        <input type="text" class="pair-right" placeholder="Sağ tərəf" required>
        <button type="button" class="btn btn-sm btn-danger remove-pair"><i class="fas fa-times"></i></button>
      </div>
    `;
    pairsList.appendChild(newPair);
    newPair.querySelector(".remove-pair").addEventListener("click", () => {
      if (modal.querySelectorAll(".pair-item").length > 2) {
        newPair.remove();
      }
    });
  });
}

setupOptionListeners();
form.addEventListener("submit", (e) => {
  e.preventDefault();

  const questionId = form.querySelector("#questionId").value;
  const questionType = form.querySelector("#questionType").value;
  const subject = form.querySelector("#questionSubject").value;
  const topic = form.querySelector("#questionTopic").value;
  const difficulty = form.querySelector("#questionDifficulty").value;
  const questionText = hiddenInput ? hiddenInput.value : '';
  const questionImage = hiddenImageInput ? hiddenImageInput.value : '';
  if (!subject || !topic || !difficulty) {
    alert("Zəhmət olmasa bütün vacib sahələri doldurun!");
    return;
  }
  
  if (!questionText && !questionImage) {
    alert("Zəhmət olmasa sual mətni və ya şəkli daxil edin!");
    return;
  }

  const questionData = {
    id: questionId,
    subject: subject,
    topic: topic,
    question_type: questionType,
    question_text: questionText,
    question_image: questionImage,
    difficulty: difficulty,
  };

  if (questionType === "multiple_choice") {
    const correctOptions = form.querySelectorAll("input[name='correctOptions']:checked");
    if (correctOptions.length === 0) {
      alert("Ən azı bir doğru cavab seçilməlidir!");
      return;
    }

    const options = Array.from(form.querySelectorAll(".option-item")).map((item, index) => {
      const editorId = item.querySelector(".option-editor").id;
      const editor = optionEditors[editorId];
      return {
        text: editor.root.innerHTML,
        isCorrect: item.querySelector("input[name='correctOptions']").checked,
      };
    });
    questionData.options = options;
  } else if (questionType === "open_ended") {
    const answer = form.querySelector("#openAnswer")?.value || "";
    questionData.correct_answer = answer;
  } else if (questionType === "true_false") {
    const checked = form.querySelector("input[name='trueFalseAnswer']:checked");
    if (checked) {
      questionData.correct_answer = checked.value;
    } else {
      alert("Doğru/Yanlış sualı üçün cavab seçilməlidir!");
      return;
    }
  } else if (questionType === "matching") {
    const pairs = Array.from(form.querySelectorAll(".pair-item"))
      .map((item) => {
        const left = item.querySelector(".pair-left").value;
        const right = item.querySelector(".pair-right").value;
        return { left, right };
      })
      .filter((pair) => pair.left && pair.right);

    if (pairs.length < 2) {
      alert("Ən azı iki cüt olmalıdır!");
      return;
    }

    questionData.pairs = pairs;
  }

  const submitButton = form.querySelector("button[type='submit']");
  const originalText = submitButton.textContent;
  submitButton.textContent = "Yüklənir...";
  submitButton.disabled = true;
  const processAndSend = async () => {
    try {
      if (questionData.question_image && isBase64Image(questionData.question_image)) {
        questionData.question_image = await compressImage(questionData.question_image);
      }
      
      const response = await fetch("movzular/suallar/operations.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(questionData),
      });
      
      const result = await response.json();
      
      if (result.success) {
        alert("Sual uğurla yeniləndi!");
        closeModal();
        window.location.reload();
      } else {
        alert("Xəta: " + (result.error || "Bilinməyən xəta baş verdi"));
      }
    } catch (error) {
      console.error("Error:", error);
      alert("Xəta baş verdi: " + error.message);
    } finally {
      submitButton.textContent = originalText;
      submitButton.disabled = false;
    }
  };
  
  processAndSend();
});

fetch(`movzular/suallar/operations.php?action=get_question&id=${id}`)
  .then((response) => response.json())
  .then((data) => {
    if (data.error) {
      showError(`Xəta: ${data.error}`);
      return;
    }
    const question = data.question;
    modal.querySelector("#questionId").value = question.id;
    const subjectSelect = modal.querySelector("#questionSubject");
    if (question.ixtisas_adi || question.subject_name) {
      const checkSubjectsLoaded = setInterval(() => {
        if (subjectSelect.options.length > 1) {
          clearInterval(checkSubjectsLoaded);
          const subjectToFind = question.ixtisas_adi || question.subject_name;
          for (let i = 0; i < subjectSelect.options.length; i++) {
            if (subjectSelect.options[i].value === subjectToFind) {
              subjectSelect.selectedIndex = i;
              const event = new Event("change");
              subjectSelect.dispatchEvent(event);
              setTimeout(() => {
                const topicSelect = modal.querySelector("#questionTopic");
                if (question.topic_id || question.topic) {
                  const topicToFind = question.topic_id || question.topic;
                  for (let j = 0; j < topicSelect.options.length; j++) {
                    if (topicSelect.options[j].value == topicToFind) {
                      topicSelect.selectedIndex = j;
                      break;
                    }
                  }
                }
              }, 500);

              break;
            }
          }
        }
      }, 100);
    }
    const questionTypeSelect = modal.querySelector("#questionType");
    questionTypeSelect.value = question.question_type || "multiple_choice";
    const typeEvent = new Event("change");
    questionTypeSelect.dispatchEvent(typeEvent);
    if (question.question_text) {
      quill.root.innerHTML = question.question_text;
      hiddenInput.value = question.question_text;
      setTimeout(() => {
        const images = quill.root.querySelectorAll("img");
        images.forEach((img) => {
          if (img.src && img.src.startsWith("data:image")) {
            img.onload = () => quill.update();
          }
        });
      }, 100);
    }
    if (question.has_image && question.question_image) {
      previewElement.innerHTML = `<img src="${question.question_image}" alt="Question Image" style="max-width: 100%; max-height: 200px;">`;
      hiddenImageInput.value = question.question_image;
    }
    const difficultySelect = modal.querySelector("#questionDifficulty");
    difficultySelect.value = question.difficulty || "";
    if (question.question_type === "multiple_choice" && question.options) {
      const optionsList = modal.querySelector("#optionsList");
      optionsList.innerHTML = ""; // Clear existing options
      let options = [];
      try {
        options = typeof question.options === "string" ? JSON.parse(question.options) : question.options;
      } catch (e) {
        console.error("Error parsing options:", e);
        options = [];
      }
      if (!options || options.length === 0) {
        options = [
          { text: "", isCorrect: false },
          { text: "", isCorrect: false },
        ];
      }
      options.forEach((option, index) => {
        const optionItem = document.createElement("div");
        optionItem.className = "form-group option-item";
        const editorId = `option-editor-edit-${index}`;
        optionItem.innerHTML = `
          <div class="option-row">
            <input type="checkbox" name="correctOptions" value="${index}" class="option-checkbox" ${option.isCorrect ? "checked" : ""}>
            <div id="${editorId}" class="option-editor quill-container"></div>
            <button type="button" class="btn btn-sm btn-danger remove-option"><i class="fas fa-times"></i></button>
          </div>
        `;
        optionsList.appendChild(optionItem);
        setTimeout(() => {
          optionEditors[editorId] = new Quill(`#${editorId}`, {
            theme: "snow",
            modules: {
              toolbar: [["bold", "italic", "underline"],["image"]],
            },
            placeholder: `Seçim ${index + 1}`,
          });

          optionEditors[editorId].root.innerHTML = option.text || "";
          optionItem.querySelector(".remove-option").addEventListener("click", () => {
            if (modal.querySelectorAll(".option-item").length > 2) {
              delete optionEditors[editorId];
              optionItem.remove();
              modal.querySelectorAll(".option-item").forEach((item, idx) => {
                item.querySelector("input[name='correctOptions']").value = idx;
              });
            }
          });
        }, 0);
      });
    } else if (question.question_type === "open_ended" && question.correct_answer) {
      let answer = "";
      try {
        const parsedAnswer = typeof question.correct_answer === "string" ? JSON.parse(question.correct_answer) : question.correct_answer;
        answer = parsedAnswer.answer || "";
      } catch (e) {
        console.error("Error parsing answer:", e);
        answer = question.correct_answer || "";
      }

      modal.querySelector("#openAnswer").value = answer;
    } else if (question.question_type === "true_false" && question.correct_answer) {
      let answer = "false";
      try {
        const parsedAnswer = typeof question.correct_answer === "string" ? JSON.parse(question.correct_answer) : question.correct_answer;
        answer = parsedAnswer.answer || "false";
      } catch (e) {
        console.error("Error parsing answer:", e);
        answer = question.correct_answer || "false";
      }

      const trueFalseValue = answer === "true" ? "true" : "false";
      const radioButton = modal.querySelector(`input[name='trueFalseAnswer'][value='${trueFalseValue}']`);
      if (radioButton) {
        radioButton.checked = true;
      }
    } else if (question.question_type === "matching" && question.correct_answer) {
      const pairsList = modal.querySelector("#pairsList");
      pairsList.innerHTML = ""; // Clear existing pairs

      let pairs = [];
      try {
        pairs = typeof question.correct_answer === "string" ? JSON.parse(question.correct_answer) : question.correct_answer;
      } catch (e) {
        console.error("Error parsing pairs:", e);
        pairs = [];
      }

      if (!pairs || pairs.length === 0) {
        pairs = [
          { left: "", right: "" },
          { left: "", right: "" },
        ];
      }

      pairs.forEach((pair, index) => {
        const pairItem = document.createElement("div");
        pairItem.className = "form-group pair-item";
        pairItem.innerHTML = `
          <div class="pair-row">
            <input type="text" class="pair-left" placeholder="Sol tərəf" required value="${pair.left || ""}">
            <i class="fas fa-arrow-right"></i>
            <input type="text" class="pair-right" placeholder="Sağ tərəf" required value="${pair.right || ""}">
            <button type="button" class="btn btn-sm btn-danger remove-pair"><i class="fas fa-times"></i></button>
          </div>
        `;
        pairsList.appendChild(pairItem);
        pairItem.querySelector(".remove-pair").addEventListener("click", () => {
          if (modal.querySelectorAll(".pair-item").length > 2) {
            pairItem.remove();
          }
        });
      });
    }

    showForm();
  })
  .catch((error) => {
    console.error("Error:", error);
    showError("Məlumatları yükləyərkən xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.");
  });

modal.style.display = "block";
setTimeout(() => {
  modal.classList.add("fade-in");
}, 10);
}

function addStyles() {
const styleElement = document.createElement('style');
styleElement.textContent = `
#imagePreview { min-height: 40px; border: 1px dashed #ccc; padding: 10px; margin-top: 5px; text-align: center;}
#imagePreview img { max-width: 100%; max-height: 200px; object-fit: contain;}
input[type="file"] { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;}
.question-image-content { text-align: center; margin-bottom: 15px;}
.question-image-content img { max-width: 100%; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);}
.question-text-content { margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border-radius: 4px; border-left: 3px solid #007bff;}
.option-view { display: flex; align-items: flex-start; margin-bottom: 8px; border: 1px solid #eee; border-radius: 4px; overflow: hidden;}
.option-letter { min-width: 30px; padding: 6px;}
.option-text { padding: 8px 12px; flex: 1;}
.compact-option img { max-height: 80px; max-width: 120px; object-fit: cover; border-radius: 3px; margin: 2px; display: inline-block; vertical-align: middle;}
.option-correct { background-color: rgba(76, 175, 80, 0.1); border-color: #4CAF50;}
.option-status { padding: 8px; color: #4CAF50; font-weight: bold; display: flex; align-items: center;}
.option-status i { margin-right: 5px;}
#previewContent .option-view { margin-bottom: 5px;}
.ql-editor img { max-height: 100px; object-fit: cover;}
@media (max-width: 768px) { 
  .option-view { flex-wrap: wrap;}
  .option-letter { min-width: 30px; padding: 6px;}
  .option-status { width: 100%; justify-content: flex-end; padding: 4px 8px;}
}
`;
document.head.appendChild(styleElement);
}

document.addEventListener('DOMContentLoaded', addStyles);
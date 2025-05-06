// Enhanced JavaScript for Payroll System

// Show alert message with animation
function showAlert(message, type = 'success') {
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '1000';
        document.body.appendChild(alertContainer);
    }
    
    // Create alert element
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type}`;
    alertElement.innerHTML = `
        ${message}
        <span class="alert-close">&times;</span>
    `;
    
    // Add close functionality
    alertElement.querySelector('.alert-close').addEventListener('click', function() {
        alertContainer.removeChild(alertElement);
    });
    
    // Add to container
    alertContainer.appendChild(alertElement);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertElement.parentNode === alertContainer) {
            alertContainer.removeChild(alertElement);
        }
    }, 5000);
}

// Improved tab functionality with animation
function openTab(evt, tabName) {
    // Get all tab content and links
    const tabContent = document.getElementsByClassName("tab-content");
    const tabLinks = document.getElementsByClassName("tab-link");
    
    // Hide all tabs with smooth transition
    for (let i = 0; i < tabContent.length; i++) {
        tabContent[i].style.display = "none";
    }
    
    // Remove active class from all tab links
    for (let i = 0; i < tabLinks.length; i++) {
        tabLinks[i].className = tabLinks[i].className.replace(" active", "");
    }
    
    // Show the selected tab with animation
    const selectedTab = document.getElementById(tabName);
    selectedTab.style.display = "block";
    
    // Add active class to the clicked button
    evt.currentTarget.className += " active";
    
    // Update URL hash for bookmarking
    history.pushState(null, null, `#${tabName}`);
}

// Enhanced toggle for employee fields with animation
function toggleEmployeeFields() {
    const employeeType = document.getElementById("employee_type").value;
    const hoursField = document.getElementById("hoursField");
    const rateField = document.getElementById("rateField");
    const grossPayField = document.getElementById("grossPayField");
    
    if (employeeType === "monthly") {
        // Fade out hourly/rate fields
        fadeOut(hoursField);
        fadeOut(rateField);
        
        // Fade in gross pay field
        fadeIn(grossPayField);
        
        // Ensure the gross pay field is required
        document.getElementById("gross_pay").setAttribute("required", "required");
        
        // Make hours and rate optional
        document.getElementById("hours").removeAttribute("required");
        document.getElementById("rate").removeAttribute("required");
    } else {
        // Fade in hourly/rate fields
        fadeIn(hoursField);
        fadeIn(rateField);
        
        // Fade out gross pay field
        fadeOut(grossPayField);
        
        // Make hours and rate required
        document.getElementById("hours").setAttribute("required", "required");
        document.getElementById("rate").setAttribute("required", "required");
    }
}

// Fade in element
function fadeIn(element) {
    element.style.display = "block";
    element.style.opacity = 0;
    
    let opacity = 0;
    const timer = setInterval(function() {
        if (opacity >= 1) {
            clearInterval(timer);
        }
        element.style.opacity = opacity;
        opacity += 0.1;
    }, 30);
    
    // Add highlight effect
    element.classList.add('highlight-field');
    setTimeout(() => {
        element.classList.remove('highlight-field');
    }, 1000);
}

// Fade out element
function fadeOut(element) {
    let opacity = 1;
    const timer = setInterval(function() {
        if (opacity <= 0) {
            clearInterval(timer);
            element.style.display = "none";
        }
        element.style.opacity = opacity;
        opacity -= 0.1;
    }, 30);
}

// Improved calculate pay with formatting
function calculatePay() {
    const employeeType = document.getElementById("employee_type").value;
    
    if (employeeType === "daily") {
        const hours = parseFloat(document.getElementById("hours").value) || 0;
        const rate = parseFloat(document.getElementById("rate").value) || 0;
        let grossPay = hours * rate;
        
        // Format to 2 decimal places
        grossPay = Math.round(grossPay * 100) / 100;
        
        document.getElementById("gross_pay").value = grossPay.toFixed(2);
        
        // Highlight the gross pay field
        highlightField("gross_pay");
        
        calculateNetPay();
    }
}

// Enhanced net pay calculation with formatting and animation
function calculateNetPay() {
    const grossPay = parseFloat(document.getElementById("gross_pay").value) || 0;
    const tax = parseFloat(document.getElementById("tax").value) || 0;
    const insurance = parseFloat(document.getElementById("insurance").value) || 0;
    const retirement = parseFloat(document.getElementById("retirement").value) || 0;
    const otherDeduction = parseFloat(document.getElementById("other_deduction").value) || 0;
    
    const totalDeductions = Math.round((tax + insurance + retirement + otherDeduction) * 100) / 100;
    const netPay = Math.round((grossPay - totalDeductions) * 100) / 100;
    
    document.getElementById("total_deductions").value = totalDeductions.toFixed(2);
    document.getElementById("net_pay").value = netPay.toFixed(2);
    
    // Highlight updated fields
    highlightField("total_deductions");
    highlightField("net_pay");
    
    // Update percentages display
    updateDeductionPercentages();
}

// Update deductions - now with percentage calculation
function updateDeductions() {
    calculateNetPay();
    
    // Optional: Auto-calculate tax based on gross pay
    const grossPay = parseFloat(document.getElementById("gross_pay").value) || 0;
    const suggestedTax = calculateSuggestedTax(grossPay);
    
    // Show suggestion but don't automatically apply
    if (grossPay > 0) {
        showTaxSuggestion(suggestedTax);
    }
}

// Calculate suggested tax based on gross pay
function calculateSuggestedTax(grossPay) {
    // Example tax brackets (modify as needed)
    if (grossPay <= 1000) {
        return grossPay * 0.1; // 10% tax
    } else if (grossPay <= 3000) {
        return grossPay * 0.15; // 15% tax
    } else {
        return grossPay * 0.2; // 20% tax
    }
}

// Show tax suggestion
function showTaxSuggestion(suggestedTax) {
    const taxField = document.getElementById("tax");
    const taxHint = document.getElementById("tax-hint") || document.createElement("div");
    
    taxHint.id = "tax-hint";
    taxHint.className = "tax-hint";
    taxHint.innerHTML = `Suggested tax: $${suggestedTax.toFixed(2)} <button class="btn-sm" onclick="applyTaxSuggestion(${suggestedTax})">Apply</button>`;
    
    if (!document.getElementById("tax-hint")) {
        taxField.parentNode.appendChild(taxHint);
    }
}

// Apply tax suggestion
function applyTaxSuggestion(amount) {
    document.getElementById("tax").value = amount.toFixed(2);
    calculateNetPay();
    highlightField("tax");
}

// Update deduction percentages for visual feedback
function updateDeductionPercentages() {
    const grossPay = parseFloat(document.getElementById("gross_pay").value) || 1; // Avoid division by zero
    const tax = parseFloat(document.getElementById("tax").value) || 0;
    const insurance = parseFloat(document.getElementById("insurance").value) || 0;
    const retirement = parseFloat(document.getElementById("retirement").value) || 0;
    const otherDeduction = parseFloat(document.getElementById("other_deduction").value) || 0;
    
    // Calculate percentages
    const taxPercent = (tax / grossPay) * 100;
    const insurancePercent = (insurance / grossPay) * 100;
    const retirementPercent = (retirement / grossPay) * 100;
    const otherPercent = (otherDeduction / grossPay) * 100;
    
    // Update percentage labels if they exist
    updatePercentLabel("tax", taxPercent);
    updatePercentLabel("insurance", insurancePercent);
    updatePercentLabel("retirement", retirementPercent);
    updatePercentLabel("other_deduction", otherPercent);
}

// Update or create percentage label
function updatePercentLabel(fieldId, percentage) {
    const field = document.getElementById(fieldId);
    let percentLabel = document.getElementById(`${fieldId}-percent`);
    
    if (!percentLabel && field) {
        percentLabel = document.createElement("span");
        percentLabel.id = `${fieldId}-percent`;
        percentLabel.className = "percent-label";
        percentLabel.style.marginLeft = "10px";
        percentLabel.style.color = "#888";
        percentLabel.style.fontSize = "0.9rem";
        field.parentNode.appendChild(percentLabel);
    }
    
    if (percentLabel) {
        percentLabel.textContent = isNaN(percentage) ? "" : `(${percentage.toFixed(1)}%)`;
    }
}

// Highlight field with animation
function highlightField(fieldId) {
    const field = document.getElementById(fieldId);
    
    // Remove existing highlight class
    field.classList.remove("highlight-field");
    
    // Trigger reflow
    void field.offsetWidth;
    
    // Add highlight class
    field.classList.add("highlight-field");
}

// Form validation with nice error messages
function validatePayrollForm() {
    const form = document.getElementById("payrollForm");
    const employeeType = document.getElementById("employee_type").value;
    
    // Reset previous error messages
    const errorMessages = form.querySelectorAll(".error-message");
    errorMessages.forEach(msg => msg.parentNode.removeChild(msg));
    
    let isValid = true;
    
    // Validate employee name
    const nameField = document.getElementById("employee_name");
    if (!nameField.value.trim()) {
        showFieldError(nameField, "Employee name is required");
        isValid = false;
    }
    
    // Validate based on employee type
    if (employeeType === "daily") {
        const hoursField = document.getElementById("hours");
        const rateField = document.getElementById("rate");
        
        if (!hoursField.value || parseFloat(hoursField.value) <= 0) {
            showFieldError(hoursField, "Valid hours are required");
            isValid = false;
        }
        
        if (!rateField.value || parseFloat(rateField.value) <= 0) {
            showFieldError(rateField, "Valid rate is required");
            isValid = false;
        }
    } else {
        const grossPayField = document.getElementById("gross_pay");
        
        if (!grossPayField.value || parseFloat(grossPayField.value) <= 0) {
            showFieldError(grossPayField, "Valid gross pay is required");
            isValid = false;
        }
    }
    
    // Validate pay date
    const payDateField = document.getElementById("pay_date");
    if (!payDateField.value) {
        showFieldError(payDateField, "Pay date is required");
        isValid = false;
    }
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    // Create error message element
    const errorMessage = document.createElement("div");
    errorMessage.className = "error-message";
    errorMessage.textContent = message;
    errorMessage.style.color = "#d9534f";
    errorMessage.style.fontSize = "0.85rem";
    errorMessage.style.marginTop = "5px";
    
    // Add error class to field
    field.classList.add("error-field");
    field.style.borderColor = "#d9534f";
    
    // Insert error message after field
    field.parentNode.insertBefore(errorMessage, field.nextSibling);
    
    // Focus the first field with error
    field.focus();
}

// Initialize form on page load with enhanced behaviors
document.addEventListener("DOMContentLoaded", function() {
    // Set up form validation
    const payrollForm = document.getElementById("payrollForm");
    if (payrollForm) {
        payrollForm.addEventListener("submit", function(e) {
            if (!validatePayrollForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Initialize employee fields
    toggleEmployeeFields();
    calculateNetPay();
    
    // Setup input event listeners for real-time calculation
    const calculationFields = [
        "hours", "rate", "gross_pay", "tax", 
        "insurance", "retirement", "other_deduction"
    ];
    
    calculationFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener("input", function() {
                if (fieldId === "hours" || fieldId === "rate") {
                    calculatePay();
                } else if (fieldId === "gross_pay") {
                    updateDeductions();
                } else {
                    calculateNetPay();
                }
            });
        }
    });
    
    // Setup employee type change event
    const employeeTypeSelect = document.getElementById("employee_type");
    if (employeeTypeSelect) {
        employeeTypeSelect.addEventListener("change", toggleEmployeeFields);
    }
    
    // Setup tab navigation from URL hash
    if (window.location.hash) {
        const tabId = window.location.hash.substring(1);
        const tabElement = document.getElementById(tabId);
        
        if (tabElement && tabElement.classList.contains("tab-content")) {
            const tabLinks = document.getElementsByClassName("tab-link");
            for (let i = 0; i < tabLinks.length; i++) {
                if (tabLinks[i].getAttribute("onclick").includes(tabId)) {
                    tabLinks[i].click();
                    break;
                }
            }
        }
    }
    
    // Create deduction summary display
    createDeductionSummary();
});

// Create visual deduction summary
function createDeductionSummary() {
    const deductionSection = document.createElement("div");
    deductionSection.className = "deductions-section";
    deductionSection.innerHTML = `
        <h4>Deduction Summary</h4>
        <div class="deduction-summary">
            <div>Gross Pay:</div>
            <div><strong id="summary-gross">$0.00</strong></div>
        </div>
        <div class="deduction-summary">
            <div>Total Deductions:</div>
            <div><strong id="summary-deductions">$0.00</strong></div>
        </div>
        <div class="deduction-summary">
            <div>Net Pay:</div>
            <div><strong id="summary-net">$0.00</strong></div>
        </div>
    `;
    
    // Find where to insert the summary
    const grossPayField = document.getElementById("grossPayField");
    if (grossPayField) {
        // Insert after gross pay field
        const nextElement = grossPayField.nextElementSibling;
        grossPayField.parentNode.insertBefore(deductionSection, nextElement);
        
        // Setup update listener
        const updateInputs = ["gross_pay", "tax", "insurance", "retirement", "other_deduction"];
        updateInputs.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener("input", updateDeductionSummary);
            }
        });
        
        // Initial update
        updateDeductionSummary();
    }
}

// Update deduction summary display
function updateDeductionSummary() {
    const grossPay = parseFloat(document.getElementById("gross_pay").value) || 0;
    const totalDeductions = parseFloat(document.getElementById("total_deductions").value) || 0;
    const netPay = parseFloat(document.getElementById("net_pay").value) || 0;
    
    const summaryGross = document.getElementById("summary-gross");
    const summaryDeductions = document.getElementById("summary-deductions");
    const summaryNet = document.getElementById("summary-net");
    
    if (summaryGross) summaryGross.textContent = `$${grossPay.toFixed(2)}`;
    if (summaryDeductions) summaryDeductions.textContent = `$${totalDeductions.toFixed(2)}`;
    if (summaryNet) summaryNet.textContent = `$${netPay.toFixed(2)}`;
}
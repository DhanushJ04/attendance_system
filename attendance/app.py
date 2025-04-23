from flask import Flask, render_template, request, redirect, session, flash, get_flashed_messages
import mysql.connector
from datetime import datetime, date

app = Flask(__name__)
app.secret_key = 'your_secret_key'  # Change this!

# MySQL Configuration
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="dhanush2003",
    database="attendance"
)
cursor = db.cursor(dictionary=True)

@app.route('/')
def home():
    return redirect('/login')

# --- Login Route ---
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        teacher_id = request.form['teacher_id']
        password = request.form['password']

        cursor.execute("SELECT * FROM teachers WHERE teacher_id = %s AND password = %s", (teacher_id, password))
        teacher = cursor.fetchone()

        if teacher:
            session['teacher_id'] = teacher_id
            session['teacher_name'] = teacher['name'] # Store teacher's name in session
            return redirect('/select_course')
        else:
            flash('❌ Invalid Teacher ID or Password.', 'error')
            return render_template('login.html')  # Don't redirect — just re-render the login page with message

    return render_template('login.html')


# --- Course Selection ---
@app.route('/select_course')
def select_course():
    # Get teacher name from the session
    teacher_name = session.get('teacher_name', 'Teacher')

    cursor.execute("SELECT * FROM courses")
    courses = cursor.fetchall()
    return render_template('select_course.html', courses=courses, teacher_name=teacher_name)

# --- Year Selection ---
@app.route('/select_year', methods=['GET'])
def select_year():
    course_id = request.args.get('course_id')
    if not course_id:
        return redirect('/select_course')

    # Store course_id in session to use in later steps
    session['course_id'] = course_id

    cursor.execute("SELECT * FROM years")
    years = cursor.fetchall()
    return render_template('select_year.html', years=years)

# --- Semester Selection ---
@app.route('/select_semester', methods=['GET'])
def select_semester():
    year_id = request.args.get('year_id')
    if not year_id:
        return redirect('/select_year')

    # Store selected year_id in session for later use
    session['year_id'] = year_id

    cursor.execute("SELECT * FROM semesters WHERE year_id = %s", (year_id,))
    semesters = cursor.fetchall()
    return render_template('select_semester.html', semesters=semesters)

# --- Subject Selection ---
@app.route('/select_subject', methods=['GET'])
def select_subject():
    semester_id = request.args.get('semester_id')
    teacher_id = session.get('teacher_id')
    course_id = session.get('course_id')  # Make sure course_id is in session

    if not all([semester_id, teacher_id, course_id]):
        return redirect('/select_semester')  # or your appropriate redirect page

    # Save semester_id to session
    session['semester_id'] = semester_id

    # Filter subjects by semester, course, and teacher
    query = """
        SELECT * FROM subjects 
        WHERE semester_id = %s AND teacher_id = %s AND course_id = %s
    """
    cursor.execute(query, (semester_id, teacher_id, course_id))
    subjects = cursor.fetchall()

    return render_template('select_subject.html', subjects=subjects)



# --- Attendance options ---
@app.route('/attendance_options', methods=['GET'])
def attendance_options():
    subject_id = request.args.get('subject_id')
    if not subject_id:
        return redirect('/select_subject')

    # Save selected subject to session for use in next steps
    session['subject_id'] = subject_id

    cursor.execute("SELECT name FROM subjects WHERE id = %s", (subject_id,))
    subject = cursor.fetchone()
    subject_name = subject['name'] if subject else "Selected Subject"

    return render_template('attendance_options.html', subject_name=subject_name)

# --- Mark or view ---
@app.route('/mark_or_view', methods=['GET'])
def mark_or_view():
    action = request.args.get('action')
    if action == 'mark':
        return redirect('/mark_attendance')
    elif action == 'view':
        return redirect('/view_attendance')
    else:
        return redirect('/attendance_options')

# --- Mark Attendance ---
@app.route('/mark_attendance', methods=['GET', 'POST'])
def mark_attendance():
    subject_id = session.get('subject_id')
    course_id = session.get('course_id')
    year_id = session.get('year_id')
    semester_id = session.get('semester_id')  # still used in session even if not in students table

    if not all([subject_id, course_id, year_id]):
        return redirect('/select_subject')  # semester_id is optional here for filtering students

    if request.method == 'POST':
        selected_date = request.form.get('date')
        if not selected_date:
            return "Date is required", 400

        # Check if attendance is already marked for the given date
        cursor.execute(
            "SELECT * FROM attendance WHERE subject_id = %s AND date = %s",
            (subject_id, selected_date)
        )
        existing_attendance = cursor.fetchone()

        if existing_attendance:
            # Redirect to confirmation page if attendance is already marked
            return redirect('/confirm_attendance')

        # Fetch students
        cursor.execute(
            "SELECT * FROM students WHERE course_id = %s AND year_id = %s",
            (course_id, year_id)
        )
        students = cursor.fetchall()

        for student in students:
            student_id = student['id']
            status = request.form.get(f'attendance_{student_id}', 'Absent')
            cursor.execute(
                "INSERT INTO attendance (student_id, subject_id, date, status) VALUES (%s, %s, %s, %s)",
                (student_id, subject_id, selected_date, status)
            )

        db.commit()

        # After successfully saving, redirect to the success page
        return redirect('/attendance_success')

    # GET method – display students
    current_date = date.today().isoformat()
    cursor.execute(
        "SELECT * FROM students WHERE course_id = %s AND year_id = %s",
        (course_id, year_id)
    )
    students = cursor.fetchall()

    return render_template('mark_attendance.html', students=students, current_date=current_date)

# --- Confirm Attendance ---
@app.route('/confirm_attendance', methods=['GET', 'POST'])
def confirm_attendance():
    if request.method == 'POST':
        selected_date = request.form.get('date')
        subject_id = session.get('subject_id')

        # Allow the teacher to mark attendance again for the same day
        cursor.execute(
            "SELECT * FROM students WHERE course_id = %s AND year_id = %s",
            (session.get('course_id'), session.get('year_id'))
        )
        students = cursor.fetchall()

        for student in students:
            student_id = student['id']
            status = request.form.get(f'attendance_{student_id}', 'Absent')
            cursor.execute(
                "INSERT INTO attendance (student_id, subject_id, date, status) VALUES (%s, %s, %s, %s)",
                (student_id, subject_id, selected_date, status)
            )

        db.commit()

        # After successfully saving, redirect to the success page
        return redirect('/attendance_success')

    return render_template('confirm_attendance.html')


# --- View Attendance ---
@app.route('/view_attendance', methods=['GET'])
def view_attendance():
    subject_id = session.get('subject_id')
    year_id = session.get('year_id')
    course_id = session.get('course_id')  # ✅ Make sure this is stored during selection

    if not subject_id or not year_id or not course_id:
        return redirect('/select_subject')

    cursor.execute("""
        SELECT 
            s.id, 
            s.name,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent
        FROM students s
        LEFT JOIN attendance a 
            ON s.id = a.student_id AND a.subject_id = %s
        WHERE s.year_id = %s AND s.course_id = %s  -- ✅ Fix here
        GROUP BY s.id, s.name
        ORDER BY s.id
    """, (subject_id, year_id, course_id))

    student_attendance = cursor.fetchall()

    return render_template('view_attendance.html', student_attendance=student_attendance)


# --- Attendance success ---
@app.route('/attendance_success')
def attendance_success():
    # Get the subject name and selected date from the URL parameters
    subject_name = request.args.get('name', 'Unknown Subject')  
    selected_date = request.args.get('selected_date', 'No date provided')
    subject_id = request.args.get('id') 

    # Render the success page with the subject name and date
    return render_template('attendance_success.html', subject_name=subject_name, selected_date=selected_date, subject_id=subject_id)

# --- Forgot Password ---
@app.route('/forgot_password', methods=['GET', 'POST'])
def forgot_password():
    if request.method == 'POST':
        teacher_id = request.form['teacher_id']
        new_password = request.form['new_password']
        confirm_password = request.form['confirm_password']

        if new_password != confirm_password:
            flash('❌ Passwords do not match.', 'error')
            return redirect('/forgot_password')

        # Check if teacher exists
        cursor.execute("SELECT * FROM teachers WHERE teacher_id = %s", (teacher_id,))
        teacher = cursor.fetchone()

        if not teacher:
            flash('⚠️ Teacher ID not found.', 'error')
            return redirect('/forgot_password')

        # Update the password 
        cursor.execute("UPDATE teachers SET password = %s WHERE teacher_id = %s", (new_password, teacher_id))
        db.commit()
        flash('✅ Password updated successfully!', 'success')
        return redirect('/login')  

    return render_template('forgot_password.html')

# --- Send Email ---
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

# SMTP email configuration
EMAIL_ADDRESS = 'noreplyedu20@gmail.com'
EMAIL_PASSWORD = 'csomtnxtlcorbgag'  # Gmail App Password

def send_attendance_email(to_email, student_name, percentage):
    subject = "Low Attendance Warning"
    body = f"""
    Dear {student_name},

    This is to inform you that your current attendance is {percentage:.2f}%.

    Please ensure your attendance is above 75% to avoid disqualification.

    Regards,
    Attendance Monitoring System
    """

    msg = MIMEMultipart()
    msg['From'] = EMAIL_ADDRESS
    msg['To'] = to_email
    msg['Subject'] = subject
    msg.attach(MIMEText(body, 'plain'))

    try:
        server = smtplib.SMTP('smtp.gmail.com', 587)
        server.starttls()
        server.login(EMAIL_ADDRESS, EMAIL_PASSWORD)
        server.send_message(msg)
        server.quit()
        print(f"Email sent to {student_name} at {to_email}")
    except Exception as e:
        print(f"❌ Failed to send email to {to_email}: {e}")

# --- Send Email Route ---
@app.route('/send_email', methods=['POST'])
def send_email():
    student_id = request.form['student_id']

    # Get student details
    cursor.execute("SELECT id, name, email FROM students WHERE id = %s", (student_id,))
    student = cursor.fetchone()

    if not student:
        flash("❌ Student not found.", "error")
        return redirect('/view_attendance')

    # Get attendance summary
    cursor.execute("""
        SELECT 
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present,
            COUNT(*) AS total
        FROM attendance
        WHERE student_id = %s
    """, (student_id,))
    result = cursor.fetchone()

    total = result['total'] or 0
    present = result['present'] or 0
    percentage = round((present / total) * 100, 2) if total > 0 else 0.0

    # Send email only if percentage is below 75
    if percentage < 75:
        send_attendance_email(student['email'], student['name'], percentage)
        flash(f"✅ Email sent to {student['name']} (Attendance: {percentage}%)", "success")
    else:
        flash(f"ℹ️ Attendance is {percentage}% for {student['name']}. No email sent.", "info")

    return redirect('/view_attendance')

# --- Add student form route ---
@app.route("/add_student_form")
def add_student_form():
    return render_template("add_student_form.html")

# --- Add student submission ---
@app.route('/add_student', methods=['POST'])
def add_student():
    if request.method == 'POST':
        student_name = request.form.get('student_name')
        student_id = request.form.get('student_id')
        email = request.form.get('email')
        course_id = request.form.get('course_id')
        year_id = request.form.get('year_id')

        if not all([student_name, student_id, email, course_id, year_id]):
            return "Missing required information", 400
        
        # Insert student into the database
        cursor.execute(
            "INSERT INTO students (name, id, course_id, year_id, email) VALUES (%s, %s, %s, %s, %s)",
            (student_name, student_id, course_id, year_id, email)
        )
        db.commit()

        # Pass a success message to the template after adding the student
        return render_template('add_student_form.html', success_message="Student added successfully!")

    return redirect('/add_student_form')  # In case of an invalid method


# --- Remove student form --- 
@app.route("/remove_student_form")
def remove_student_form():
    course_id = session.get('course_id')
    year_id = session.get('year_id')

    if not course_id or not year_id:
        return "Course or Year not selected", 400

    cursor.execute("""
        SELECT id, name, course_id, year_id 
        FROM students 
        WHERE course_id = %s AND year_id = %s
    """, (course_id, year_id))
    students = cursor.fetchall()

    return render_template("remove_student_form.html", students=students)



# --- Remove student --- 
@app.route('/remove_student', methods=['POST'])
def remove_student():
    if request.method == 'POST':
        student_ids_to_remove = request.form.getlist('students_to_remove')
        
        if not student_ids_to_remove:
            return "No students selected for removal", 400
        
        for student_id in student_ids_to_remove:
            # First, remove the related attendance records
            cursor.execute("DELETE FROM attendance WHERE student_id = %s", (student_id,))
            
            # Now, remove the student from the students table
            cursor.execute("DELETE FROM students WHERE id = %s", (student_id,))
        
        db.commit()

        flash("✅ Student(s) removed successfully!")
        return redirect('/mark_attendance')

    return redirect('/mark_attendance')



if __name__ == '__main__':
    app.run(debug=True)
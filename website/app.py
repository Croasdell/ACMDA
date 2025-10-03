

from flask import Flask, render_template, request, redirect, url_for, session, flash
import sqlite3
import os
import sys
from flask_wtf import FlaskForm
from wtforms import StringField, PasswordField, SelectField, DateField
from wtforms.validators import DataRequired
from flask_wtf.csrf import CSRFProtect

sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from email_notify import send_notification

app = Flask(__name__)
app.secret_key = os.environ.get('FLASK_SECRET_KEY', 'change_this_secret')
csrf = CSRFProtect(app)

ADMIN_PASSWORD = os.environ.get('ADMIN_PASSWORD', 'admin123')

def is_admin():
    return session.get('admin_logged_in', False)

class BookingForm(FlaskForm):
    name = StringField('Name', validators=[DataRequired()])
    service = SelectField('Service', choices=[
        ("Property maintenance", "Property maintenance"),
        ("General repairs", "General repairs"),
        ("Installations (non-gas, non-electrical)", "Installations (non-gas, non-electrical)"),
        ("Flat roof repairs (no sloped roof work)", "Flat roof repairs (no sloped roof work)"),
        ("Small moving/van transport (non-dangerous items only)", "Small moving/van transport (non-dangerous items only)"),
        ("Flatpack assembly", "Flatpack assembly"),
        ("Painting & decorating", "Painting & decorating"),
        ("Carpentry", "Carpentry")
    ], validators=[DataRequired()])
    date = DateField('Date', validators=[DataRequired()])
    contact = StringField('Contact', validators=[DataRequired()])

class AdminLoginForm(FlaskForm):
    password = PasswordField('Password', validators=[DataRequired()])

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/services')
def services():
    return render_template('services.html')


@app.route('/booking', methods=['GET', 'POST'])
def booking():
    form = BookingForm()
    if request.method == 'POST' and form.validate_on_submit():
        name = form.name.data
        service = form.service.data
        date = form.date.data.strftime('%Y-%m-%d')
        contact = form.contact.data
        conn = sqlite3.connect('../acmda.db')
        c = conn.cursor()
        c.execute("INSERT INTO bookings (name, service, date, contact) VALUES (?, ?, ?, ?)",
                  (name, service, date, contact))
        conn.commit()
        conn.close()
        # Email notification to admin
        try:
            send_notification(
                to_email="iancroasdell@gmail.com",
                subject="New Booking Received",
                body=f"New booking from {name} for {service} on {date}. Contact: {contact}"
            )
        except Exception as e:
            print(f"Email notification failed: {e}")
        # Email confirmation to customer
        try:
            send_notification(
                to_email=contact,
                subject="Your Booking is Confirmed",
                body=f"Thank you {name}, your booking for {service} on {date} has been received. We'll be in touch soon."
            )
        except Exception as e:
            print(f"Customer email failed: {e}")
        # WhatsApp notification stub
        try:
            from whatsapp_integration import send_whatsapp_message
            send_whatsapp_message("+447729689420", f"New booking from {name} for {service} on {date}. Contact: {contact}")
        except Exception as e:
            print(f"WhatsApp notification failed: {e}")
        return redirect(url_for('booking_confirmation'))
    return render_template('booking.html', form=form)

@app.route('/booking/confirmation')
def booking_confirmation():
    return render_template('booking_confirmation.html')

@app.route('/contact')
def contact():
    return render_template('contact.html')

# Admin login/logout
@app.route('/admin/login', methods=['GET', 'POST'])
def admin_login():
    form = AdminLoginForm()
    if form.validate_on_submit():
        password = form.password.data
        if password == ADMIN_PASSWORD:
            session['admin_logged_in'] = True
            return redirect(url_for('admin_dashboard'))
        else:
            flash('Incorrect password.')
    return render_template('admin_login.html', form=form)

@app.route('/admin/logout')
def admin_logout():
    session.pop('admin_logged_in', None)
    flash('Logged out.')
    return redirect(url_for('home'))


# Admin dashboard (view/edit/delete bookings, search/filter)
@app.route('/admin/dashboard', methods=['GET', 'POST'])
def admin_dashboard():
    if not is_admin():
        return redirect(url_for('admin_login'))
    search = request.args.get('search', '').strip()
    filter_service = request.args.get('service', '').strip()
    query = "SELECT * FROM bookings"
    params = []
    where = []
    if search:
        where.append("(name LIKE ? OR contact LIKE ?)")
        params.extend([f"%{search}%", f"%{search}%"])
    if filter_service:
        where.append("service = ?")
        params.append(filter_service)
    if where:
        query += " WHERE " + " AND ".join(where)
    query += " ORDER BY date DESC"
    conn = sqlite3.connect('../acmda.db')
    c = conn.cursor()
    c.execute(query, params)
    bookings = c.fetchall()
    # Get all unique services for filter dropdown
    c.execute("SELECT DISTINCT service FROM bookings")
    services = [row[0] for row in c.fetchall()]
    conn.close()
    return render_template('admin_dashboard.html', bookings=bookings, services=services, search=search, filter_service=filter_service)

@app.route('/admin/delete/<int:booking_id>', methods=['POST'])
def admin_delete_booking(booking_id):
    if not is_admin():
        return redirect(url_for('admin_login'))
    conn = sqlite3.connect('../acmda.db')
    c = conn.cursor()
    c.execute("DELETE FROM bookings WHERE id = ?", (booking_id,))
    conn.commit()
    conn.close()
    flash('Booking deleted.')
    return redirect(url_for('admin_dashboard'))

# Admin edit booking
class EditBookingForm(FlaskForm):
    name = StringField('Name', validators=[DataRequired()])
    service = SelectField('Service', choices=[
        ("Property maintenance", "Property maintenance"),
        ("General repairs", "General repairs"),
        ("Installations (non-gas, non-electrical)", "Installations (non-gas, non-electrical)"),
        ("Flat roof repairs (no sloped roof work)", "Flat roof repairs (no sloped roof work)"),
        ("Small moving/van transport (non-dangerous items only)", "Small moving/van transport (non-dangerous items only)"),
        ("Flatpack assembly", "Flatpack assembly"),
        ("Painting & decorating", "Painting & decorating"),
        ("Carpentry", "Carpentry")
    ], validators=[DataRequired()])
    date = DateField('Date', validators=[DataRequired()])
    contact = StringField('Contact', validators=[DataRequired()])

@app.route('/admin/edit/<int:booking_id>', methods=['GET', 'POST'])
def admin_edit_booking(booking_id):
    if not is_admin():
        return redirect(url_for('admin_login'))
    conn = sqlite3.connect('../acmda.db')
    c = conn.cursor()
    c.execute("SELECT * FROM bookings WHERE id = ?", (booking_id,))
    booking = c.fetchone()
    if not booking:
        conn.close()
        flash('Booking not found.')
        return redirect(url_for('admin_dashboard'))
    form = EditBookingForm()
    if request.method == 'GET':
        form.name.data = booking[1]
        form.service.data = booking[2]
        form.date.data = booking[3]
        form.contact.data = booking[4]
    if form.validate_on_submit():
        c.execute("UPDATE bookings SET name=?, service=?, date=?, contact=? WHERE id=?",
                  (form.name.data, form.service.data, form.date.data.strftime('%Y-%m-%d'), form.contact.data, booking_id))
        conn.commit()
        conn.close()
        flash('Booking updated.')
        return redirect(url_for('admin_dashboard'))
    conn.close()
    return render_template('admin_edit_booking.html', form=form, booking_id=booking_id)

if __name__ == '__main__':
    app.run(debug=True)

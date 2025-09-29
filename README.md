# Cake Calculator

A PHP command-line utility to track employee birthday cakes based on complex business rules.

## Features

- 📅 Calculates cake days based on employee birthdays
- 🎂 Handles different cake types (small vs large)
- 🏢 Accounts for working days, weekends, and holidays
- 📊 Exports results to CSV format
- 🧪 Comprehensive test coverage

## Business Rules

1. **Small cake** is provided on the employee's first working day after their birthday
2. All employees get their **birthday off**
3. Office is closed on **weekends**, **Christmas Day**, **Boxing Day** and **New Year's Day**
4. If office is closed on employee's birthday, they get the **next working day off**
5. If **two or more** cake days coincide, provide **one large cake** to share
6. If there is cake **two days in a row**, provide **one large cake on the second day**
7. Day after each cake must be **cake-free** - any cakes due are postponed
8. **Never more than one cake a day**

## Installation

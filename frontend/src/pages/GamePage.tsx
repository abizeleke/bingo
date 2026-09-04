import { useEffect, useMemo, useState } from "react";
import "./GamePage.css";

// ============================================================
// GAME CONFIG
// ============================================================

const GAME_START_ID = 125;

const ENTRY_SECONDS = 50;
const ENTRY_FEE = 10;

const MAX_CARD_NUMBER = 200;
const MAX_SELECTED_CARDS = 3;

const REQUIRED_PLAYERS = 10;
const COMMISSION_RATE = 0.10;

const CALL_INTERVAL = 1800;

// ============================================================
// RANDOM
// ============================================================

function seededRandom(seed) {
  let x = Math.sin(seed) * 10000;
  return x - Math.floor(x);
}

function shuffle(array, seed = Math.random() * 100000) {
  const result = [...array];

  for (let i = result.length - 1; i > 0; i--) {
    const random = seededRandom(
      seed + i * 19.731
    );

    const j = Math.floor(
      random * (i + 1)
    );

    [result[i], result[j]] = [
      result[j],
      result[i],
    ];
  }

  return result;
}

// ============================================================
// BINGO CARD GENERATOR
// ============================================================

function generateCard(gameId, cardNumber) {
  const seed =
    gameId * 1000 + cardNumber;

  const ranges = [
    [1, 15],
    [16, 30],
    [31, 45],
    [46, 60],
    [61, 75],
  ];

  const card = [];

  ranges.forEach(
    ([min, max], columnIndex) => {
      const numbers = [];

      for (
        let n = min;
        n <= max;
        n++
      ) {
        numbers.push(n);
      }

      const shuffled = shuffle(
        numbers,
        seed + columnIndex * 100
      );

      for (let row = 0; row < 5; row++) {
        if (
          columnIndex === 2 &&
          row === 2
        ) {
          card.push({
            number: "FREE",
            column: columnIndex,
            row,
          });
        } else {
          card.push({
            number: shuffled[row],
            column: columnIndex,
            row,
          });
        }
      }
    }
  );

  return card;
}

// ============================================================
// WINNING PATTERN
// ============================================================

function getWinningPattern(
  card,
  calledNumbers
) {
  if (!card) return null;

  const called = new Set(
    calledNumbers
  );

  const marked = card.map(
    (cell) => {
      if (cell.number === "FREE") {
        return true;
      }

      return called.has(
        cell.number
      );
    }
  );

  // HORIZONTAL
  for (let row = 0; row < 5; row++) {
    const indexes = [];

    for (
      let col = 0;
      col < 5;
      col++
    ) {
      indexes.push(
        col * 5 + row
      );
    }

    if (
      indexes.every(
        (i) => marked[i]
      )
    ) {
      return {
        type: "HORIZONTAL",
        cells: indexes,
      };
    }
  }

  // VERTICAL
  for (let col = 0; col < 5; col++) {
    const indexes = [];

    for (
      let row = 0;
      row < 5;
      row++
    ) {
      indexes.push(
        col * 5 + row
      );
    }

    if (
      indexes.every(
        (i) => marked[i]
      )
    ) {
      return {
        type: "VERTICAL",
        cells: indexes,
      };
    }
  }

  // DIAGONAL
  const diagonal1 = [
    0,
    6,
    12,
    18,
    24,
  ];

  if (
    diagonal1.every(
      (i) => marked[i]
    )
  ) {
    return {
      type: "DIAGONAL",
      cells: diagonal1,
    };
  }

  const diagonal2 = [
    4,
    8,
    12,
    16,
    20,
  ];

  if (
    diagonal2.every(
      (i) => marked[i]
    )
  ) {
    return {
      type: "DIAGONAL",
      cells: diagonal2,
    };
  }

  // FOUR CORNERS
  const corners = [
    0,
    4,
    20,
    24,
  ];

  if (
    corners.every(
      (i) => marked[i]
    )
  ) {
    return {
      type: "FOUR CORNERS",
      cells: corners,
    };
  }

  return null;
}

// ============================================================
// HELPERS
// ============================================================

function getLetter(number) {
  if (number <= 15) return "B";
  if (number <= 30) return "I";
  if (number <= 45) return "N";
  if (number <= 60) return "G";
  return "O";
}

function getPrizePool(players) {
  return (
    players *
    ENTRY_FEE *
    (1 - COMMISSION_RATE)
  );
}

// ============================================================
// BINGO CARD
// ============================================================

function BingoCard({
  card,
  calledNumbers = [],
  compact = false,
  winner = false,
}) {
  if (!card) return null;

  const called = new Set(
    calledNumbers
  );

  return (
    <div
      className={`casino-bingo-card ${
        compact
          ? "mini-bingo"
          : ""
      } ${
        winner
          ? "winner-card"
          : ""
      }`}
    >
      {/* B I N G O */}
      <div className="bingo-letters">
        {[
          "B",
          "I",
          "N",
          "G",
          "O",
        ].map((letter) => (
          <div
            key={letter}
            className={`bingo-letter letter-${letter}`}
          >
            {letter}
          </div>
        ))}
      </div>

      {/* CARD NUMBERS */}
      <div className="bingo-grid">
        {card.map(
          (cell, index) => {
            const marked =
              cell.number ===
                "FREE" ||
              called.has(
                cell.number
              );

            return (
              <div
                key={index}
                className={`bingo-number ${
                  marked
                    ? "bingo-number-marked"
                    : ""
                } ${
                  cell.number ===
                  "FREE"
                    ? "free-space"
                    : ""
                }`}
              >
                {cell.number ===
                "FREE"
                  ? "★"
                  : cell.number}
              </div>
            );
          }
        )}
      </div>
    </div>
  );
}

// ============================================================
// TOP CARDS
// ============================================================

function TopCards({
  gameId,
  balance,
  time,
  players,
  prizePool,
}) {
  return (
    <>
      {/* CARD 1 */}
      <section className="casino-top-card card-one">
        <div className="casino-info">
          <span>GAME</span>

          <strong>
            #{gameId}
          </strong>
        </div>

        <div className="gold-separator" />

        <div className="casino-info">
          <span>BALANCE</span>

          <strong className="money">
            {balance.toFixed(0)} ETB
          </strong>
        </div>
      </section>

      {/* CARD 2 */}
      <section className="casino-top-card card-two">
        <div className="casino-info">
          <span>TIME</span>

          <strong className="timer-value">
            {time}s
          </strong>
        </div>

        <div className="casino-info">
          <span>PLAYERS</span>

          <strong>
            {players}
          </strong>
        </div>

        <div className="casino-info">
          <span>PRIZE POOL</span>

          <strong className="money">
            {prizePool.toFixed(0)}
          </strong>
        </div>
      </section>
    </>
  );
}

// ============================================================
// START POPUP
// ============================================================

function StartPopup({
  countdown,
}) {
  return (
    <div className="casino-overlay">
      <div className="start-popup">
        <div className="casino-crown">
          ♛
        </div>

        <div className="popup-small">
          NEXT ROUND
        </div>

        <h1>
          GAME STARTING
        </h1>

        <div className="gold-line" />

        <div className="big-countdown">
          {countdown}
        </div>

        <div className="popup-bottom">
          Game starts in{" "}
          <b>
            {countdown} sec
          </b>
        </div>
      </div>
    </div>
  );
}

// ============================================================
// WINNER POPUP
// ============================================================

function WinnerPopup({
  winner,
  amount,
  card,
  calledNumbers,
  countdown,
}) {
  return (
    <div className="casino-overlay">
      <div className="winner-popup">
        <div className="winner-crown">
          👑
        </div>

        <div className="winner-label">
          JACKPOT WINNER
        </div>

        <h1>
          {winner}
        </h1>

        <div className="winner-money">
          +{amount.toFixed(0)} ETB
        </div>

        <div className="winner-badge">
          ★ BINGO ★
        </div>

        <div className="winner-card-wrap">
          <BingoCard
            card={card}
            calledNumbers={
              calledNumbers
            }
            compact
            winner
          />
        </div>

        <div className="next-game">
          Next game starts in{" "}
          <b>
            {countdown} sec
          </b>
        </div>
      </div>
    </div>
  );
}

// ============================================================
// APP
// ============================================================

export default function App() {
  const [phase, setPhase] =
    useState("entry");

  const [gameId, setGameId] =
    useState(
      GAME_START_ID
    );

  const [balance, setBalance] =
    useState(50);

  const [entryTime, setEntryTime] =
    useState(
      ENTRY_SECONDS
    );

  const [selectedCards, setSelectedCards] =
    useState([]);

  const [botCards, setBotCards] =
    useState([]);

  const [calledNumbers, setCalledNumbers] =
    useState([]);

  const [callSequence, setCallSequence] =
    useState([]);

  const [callIndex, setCallIndex] =
    useState(0);

  const [countdown, setCountdown] =
    useState(3);

  const [winner, setWinner] =
    useState(null);

  const [winnerAmount, setWinnerAmount] =
    useState(0);

  const [winnerCard, setWinnerCard] =
    useState(null);

  // ==========================================================
  // DATA
  // ==========================================================

  const totalPlayers =
    selectedCards.length +
    botCards.length;

  const prizePool =
    getPrizePool(
      totalPlayers
    );

  const userCards = useMemo(
    () => {
      return selectedCards.map(
        (number) => ({
          number,
          card: generateCard(
            gameId,
            number
          ),
        })
      );
    },
    [
      selectedCards,
      gameId,
    ]
  );

  // ==========================================================
  // ENTRY TIMER
  // ==========================================================

  useEffect(() => {
    if (phase !== "entry") {
      return;
    }

    const timer =
      setInterval(() => {
        setEntryTime(
          (old) => {
            if (old <= 1) {
              clearInterval(
                timer
              );

              setCountdown(3);
              setPhase(
                "starting"
              );

              return 0;
            }

            return old - 1;
          }
        );
      }, 1000);

    return () =>
      clearInterval(timer);
  }, [phase]);

  // ==========================================================
  // BOT PLAYERS
  // ==========================================================

  useEffect(() => {
    if (phase !== "entry") {
      return;
    }

    const needed =
      REQUIRED_PLAYERS -
      selectedCards.length;

    if (
      botCards.length >=
      needed
    ) {
      return;
    }

    const timer =
      setInterval(() => {
        setBotCards(
          (old) => {
            const required =
              REQUIRED_PLAYERS -
              selectedCards.length;

            if (
              old.length >=
              required
            ) {
              return old;
            }

            const occupied =
              new Set([
                ...selectedCards,
                ...old,
              ]);

            const available =
              [];

            for (
              let i = 1;
              i <=
              MAX_CARD_NUMBER;
              i++
            ) {
              if (
                !occupied.has(
                  i
                )
              ) {
                available.push(
                  i
                );
              }
            }

            if (
              !available.length
            ) {
              return old;
            }

            const number =
              available[
                Math.floor(
                  Math.random() *
                    available.length
                )
              ];

            return [
              ...old,
              number,
            ];
          }
        );
      }, 5000);

    return () =>
      clearInterval(timer);
  }, [
    phase,
    selectedCards,
    botCards,
  ]);

  // ==========================================================
  // START COUNTDOWN
  // ==========================================================

  useEffect(() => {
    if (
      phase !== "starting"
    ) {
      return;
    }

    const timer =
      setInterval(() => {
        setCountdown(
          (old) => {
            if (old <= 1) {
              clearInterval(
                timer
              );

              beginGame();

              return 0;
            }

            return old - 1;
          }
        );
      }, 1000);

    return () =>
      clearInterval(timer);
  }, [phase]);

  // ==========================================================
  // BEGIN GAME
  // ==========================================================

  function beginGame() {
    const numbers = [];

    for (
      let i = 1;
      i <= 75;
      i++
    ) {
      numbers.push(i);
    }

    const sequence =
      shuffle(
        numbers,
        gameId * 839 + 71
      );

    setCallSequence(
      sequence
    );

    setCallIndex(0);

    setCalledNumbers([]);

    setWinner(null);

    setWinnerCard(null);

    setWinnerAmount(0);

    setPhase("game");
  }

  // ==========================================================
  // CALL RANDOM NUMBERS
  // ==========================================================

  useEffect(() => {
    if (phase !== "game") {
      return;
    }

    if (
      callIndex >=
      callSequence.length
    ) {
      return;
    }

    const timer =
      setTimeout(() => {
        const next =
          callSequence[
            callIndex
          ];

        setCalledNumbers(
          (old) => [
            ...old,
            next,
          ]
        );

        setCallIndex(
          (old) =>
            old + 1
        );
      }, CALL_INTERVAL);

    return () =>
      clearTimeout(timer);
  }, [
    phase,
    callIndex,
    callSequence,
  ]);

  // ==========================================================
  // WINNER CHECK
  // ==========================================================

  useEffect(() => {
    if (phase !== "game") {
      return;
    }

    if (
      !calledNumbers.length
    ) {
      return;
    }

    const participants = [
      ...selectedCards.map(
        (number) => ({
          name: "YOU",
          number,
          card: generateCard(
            gameId,
            number
          ),
        })
      ),

      ...botCards.map(
        (number, index) => ({
          name: `PLAYER ${
            index + 2
          }`,
          number,
          card: generateCard(
            gameId,
            number
          ),
        })
      ),
    ];

    const winners =
      participants.filter(
        (participant) =>
          getWinningPattern(
            participant.card,
            calledNumbers
          )
      );

    if (
      !winners.length
    ) {
      return;
    }

    const share =
      prizePool /
      winners.length;

    const firstWinner =
      winners[0];

    setWinner({
      name: firstWinner.name,
      cardNumber:
        firstWinner.number,
    });

    setWinnerCard(
      firstWinner.card
    );

    setWinnerAmount(
      share
    );

    setCountdown(3);

    setPhase("winner");
  }, [
    calledNumbers,
    phase,
    selectedCards,
    botCards,
    gameId,
    prizePool,
  ]);

  // ==========================================================
  // WINNER COUNTDOWN
  // ==========================================================

  useEffect(() => {
    if (
      phase !== "winner"
    ) {
      return;
    }

    const timer =
      setInterval(() => {
        setCountdown(
          (old) => {
            if (old <= 1) {
              clearInterval(
                timer
              );

              nextGame();

              return 0;
            }

            return old - 1;
          }
        );
      }, 1000);

    return () =>
      clearInterval(timer);
  }, [phase]);

  // ==========================================================
  // NEXT GAME
  // ==========================================================

  function nextGame() {
    setGameId(
      (old) => old + 1
    );

    setEntryTime(
      ENTRY_SECONDS
    );

    setSelectedCards([]);

    setBotCards([]);

    setCalledNumbers([]);

    setCallSequence([]);

    setCallIndex(0);

    setWinner(null);

    setWinnerCard(null);

    setWinnerAmount(0);

    setPhase("entry");
  }

  // ==========================================================
  // SELECT / UNSELECT CARD
  // ==========================================================

  function toggleCard(
    number
  ) {
    if (
      botCards.includes(
        number
      )
    ) {
      return;
    }

    // UNSELECT
    if (
      selectedCards.includes(
        number
      )
    ) {
      setSelectedCards(
        (old) =>
          old.filter(
            (n) =>
              n !== number
          )
      );

      setBalance(
        (old) =>
          old + ENTRY_FEE
      );

      return;
    }

    // MAX 3
    if (
      selectedCards.length >=
      MAX_SELECTED_CARDS
    ) {
      return;
    }

    // BALANCE
    if (
      balance <
      ENTRY_FEE
    ) {
      return;
    }

    setSelectedCards(
      (old) => [
        ...old,
        number,
      ]
    );

    setBalance(
      (old) =>
        old - ENTRY_FEE
    );
  }

  // ==========================================================
  // RECENT NUMBERS
  // ==========================================================

  const recentNumbers =
    [
      ...calledNumbers,
    ]
      .reverse()
      .slice(0, 5);

  // ==========================================================
  // ENTRY
  // ==========================================================

  if (phase === "entry") {
    return (
      <div className="screen entry-screen">

        <TopCards
          gameId={gameId}
          balance={balance}
          time={entryTime}
          players={
            totalPlayers
          }
          prizePool={
            prizePool
          }
        />

        {/* CARD SELECTOR */}
        <section className="casino-panel selector-panel">

          <div className="panel-header">
            <div>
              <h2>
                CHOOSE YOUR CARDS
              </h2>

              <small>
                Pick up to 3 cards
              </small>
            </div>

            <div className="card-counter">
              {
                selectedCards.length
              }
              /3
            </div>
          </div>

          <div className="number-scroll">
            <div className="number-grid">

              {Array.from(
                {
                  length:
                    MAX_CARD_NUMBER,
                },
                (_, index) =>
                  index + 1
              ).map(
                (number) => {
                  const selected =
                    selectedCards.includes(
                      number
                    );

                  const bot =
                    botCards.includes(
                      number
                    );

                  return (
                    <button
                      key={
                        number
                      }
                      onClick={() =>
                        toggleCard(
                          number
                        )
                      }
                      className={`casino-number ${
                        selected
                          ? "user-card-number"
                          : ""
                      } ${
                        bot
                          ? "bot-card-number"
                          : ""
                      }`}
                      disabled={
                        bot ||
                        (!selected &&
                          selectedCards.length >=
                            MAX_SELECTED_CARDS)
                      }
                    >
                      {
                        number
                      }
                    </button>
                  );
                }
              )}

            </div>
          </div>
        </section>

        {/* SELECTED CARDS */}
        <section className="casino-panel selected-panel">

          {selectedCards.length ===
          0 ? (
            <div className="empty-casino">
              <div className="empty-icon">
                ♣
              </div>

              <span>
                SELECT A CARD TO PLAY
              </span>
            </div>
          ) : (
            <div className="selected-casino-cards">

              {userCards.map(
                ({
                  number,
                  card,
                }) => (
                  <div
                    key={
                      number
                    }
                    className="entry-bingo-wrapper"
                  >
                    <BingoCard
                      card={card}
                      compact
                    />
                  </div>
                )
              )}

            </div>
          )}

        </section>
      </div>
    );
  }

  // ==========================================================
  // STARTING
  // ==========================================================

  if (
    phase === "starting"
  ) {
    return (
      <div className="screen">

        <TopCards
          gameId={gameId}
          balance={balance}
          time={0}
          players={
            totalPlayers
          }
          prizePool={
            prizePool
          }
        />

        <div className="blank-area" />

        <StartPopup
          countdown={
            countdown
          }
        />

      </div>
    );
  }

  // ==========================================================
  // WINNER
  // ==========================================================

  if (
    phase === "winner"
  ) {
    return (
      <div className="screen">

        <TopCards
          gameId={gameId}
          balance={balance}
          time={0}
          players={
            totalPlayers
          }
          prizePool={
            prizePool
          }
        />

        <div className="blank-area" />

        <WinnerPopup
          winner={
            winner?.name ||
            "PLAYER"
          }
          amount={
            winnerAmount
          }
          card={
            winnerCard
          }
          calledNumbers={
            calledNumbers
          }
          countdown={
            countdown
          }
        />

      </div>
    );
  }

  // ==========================================================
  // ACTIVE GAME
  // ==========================================================

  return (
    <div className="screen game-screen">

      <TopCards
        gameId={gameId}
        balance={balance}
        time={
          calledNumbers.length
        }
        players={
          totalPlayers
        }
        prizePool={
          prizePool
        }
      />

      <section className="casino-game-area">

        {/* ==================================================
            LEFT SIDE
        ================================================== */}

        <div className="called-area">

          <div className="game-title">

            <div>
              <span>
                LIVE BINGO
              </span>

              <h2>
                CALLED NUMBERS
              </h2>
            </div>

            <strong>
              {
                calledNumbers.length
              }
              /75
            </strong>

          </div>

          {/* RECENT */}
          <div className="recent-area">

            <div className="recent-heading">
              RECENT CALLS
            </div>

            <div className="recent-list">

              {recentNumbers.length ===
              0 ? (
                <div className="waiting-call">
                  WAITING...
                </div>
              ) : (
                recentNumbers.map(
                  (
                    number,
                    index
                  ) => (
                    <div
                      key={
                        number
                      }
                      className={`casino-ball ${
                        index === 0
                          ? "newest-ball"
                          : ""
                      }`}
                    >
                      <small>
                        {
                          getLetter(
                            number
                          )
                        }
                      </small>

                      <strong>
                        {
                          number
                        }
                      </strong>
                    </div>
                  )
                )
              )}

            </div>
          </div>

          {/* 1 - 75 */}
          <div className="casino-board">

            {[
              "B",
              "I",
              "N",
              "G",
              "O",
            ].map(
              (letter) => (
                <div
                  className="casino-column"
                  key={
                    letter
                  }
                >

                  <div
                    className={`casino-column-title column-${letter}`}
                  >
                    {
                      letter
                    }
                  </div>

                  {Array.from(
                    {
                      length: 15,
                    },
                    (
                      _,
                      index
                    ) => {
                      const number =
                        index +
                        1 +
                        {
                          B: 0,
                          I: 15,
                          N: 30,
                          G: 45,
                          O: 60,
                        }[
                          letter
                        ];

                      const called =
                        calledNumbers.includes(
                          number
                        );

                      return (
                        <div
                          key={
                            number
                          }
                          className={`board-ball ${
                            called
                              ? "board-ball-called"
                              : ""
                          }`}
                        >
                          {
                            number
                          }
                        </div>
                      );
                    }
                  )}

                </div>
              )
            )}

          </div>
        </div>

        {/* DIVIDER */}
        <div className="casino-divider" />

        {/* ==================================================
            RIGHT SIDE
        ================================================== */}

        <div className="my-cards-area">

          <div className="my-cards-title">

            <span>
              YOUR BINGO CARDS
            </span>

            <small>
              {
                selectedCards.length
              } ACTIVE
            </small>

          </div>

          <div className="my-cards-scroll">

            {userCards.map(
              ({
                number,
                card,
              }) => (
                <div
                  key={
                    number
                  }
                  className="game-card-wrapper"
                >

                  <div className="game-card-number">
                    CARD #{number}
                  </div>

                  <BingoCard
                    card={card}
                    calledNumbers={
                      calledNumbers
                    }
                  />

                </div>
              )
            )}

          </div>

        </div>

      </section>
    </div>
  );
}